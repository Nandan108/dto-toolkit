<?php

namespace Nandan108\DtoToolkit;

use Attribute;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\CasterResolverInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * Defines a casting method for a DTO property during normalization.
 * Should be used through concrete Caster Attribute subclasses.
 * #[CastTo('SomeType')]
 *
 * Can also be used directly with a DTO caster method name or a CasterInterface class name.
 * Usage:
 *   #[CasterCore('SomeType')]
 *   public string|SomeType|null $property;
 *
 * This will call the method castToSomeType($value) on the DTO.
 *
 * - The provided value must match the suffix of a method named castTo{$value}()
 * - The optional `$outbound` flag specifies if the cast is applied during output
 * normalization, meaning before returning an entity or array.
 *
 * @psalm-api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CastTo
{
    static protected string $methodPrefix = 'castTo';
    public static ?CasterResolverInterface $customCasterResolver = null;
    protected static ?\stdClass $globalMemoizedCasters = null;
    public static \Closure $onCastResolved;

    public function __construct(
        public ?string $methodOrClass = null,
        /** @psalm-suppress PossiblyUnusedProperty */
        public bool $outbound = false,
        public array $args = [],
        public ?array $constructorArgs = null,
    ) {
        // Initialize the caster cache if it doesn't exist
        self::$globalMemoizedCasters ??= new \stdClass();
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        self::$onCastResolved ??= fn(): null => null;
    }

    /**
     * Create a caster closure for the given method
     *
     * @param mixed $dto The DTO instance
     * @throws \LogicException If the method does not exist
     * @return \Closure A closure that takes a value to cast calls the casting method and returns the result.
     */
    public function getCaster(?BaseDto $dto = null): \Closure
    {
        $serialize      = fn(mixed $data, $prefix      = '') => $data ? $prefix . serialize($data) : 'null';
        $cache          = static::$globalMemoizedCasters;
        $args           = $this->args;
        $serializedArgs = $serialize($args);
        $cacheKey       = $this->methodOrClass . $serialize($this->constructorArgs, ':');

        // early return on cache hit
        $casterMeta = $cache->$cacheKey['casters'][$serializedArgs] ?? null;
        if ($casterMeta !== null) {
            (static::$onCastResolved)($casterMeta, true);
            return $casterMeta['caster'];
        }

        $memoize = function (\Closure $caster, ?string $object, string $method) use (&$cache, $cacheKey, $serializedArgs): \Closure {
            $cache->$cacheKey['casters'][$serializedArgs] = $casterMeta = compact('caster', 'object', 'method');
            (static::$onCastResolved)($casterMeta, false);
            return $caster;
        };

        // Step 1: Class name resolution
        if ($this instanceof CasterInterface) {
            $instance = $cache->$cacheKey['instance'] ??= $this;
        } elseif (class_exists($this->methodOrClass)) {
            $instance = $cache->$cacheKey['instance'] ??= $this->resolveFromClass($this->methodOrClass);
        }
        // if we have an caster class instance, return a caster
        if (isset($instance)) {
            return $memoize(
                caster: fn(mixed $value): mixed => $instance->cast($value, $args),
                object: $this->methodOrClass,
                method: static::$methodPrefix . ucfirst($this->methodOrClass),
            );
        }

        // Step 2 & 3: Method resolution on DTO and CastTo
        $methodName = static::$methodPrefix . ucfirst($this->methodOrClass);
        foreach (array_filter([$dto, $this]) as $target) {
            if (method_exists($target, $methodName)) {
                return $memoize(
                    fn(mixed $value): mixed => $target->{$methodName}($value, ...$args),
                        $target::class,
                    $methodName,
                );
            }
        }

        // Step 4: Custom resolver
        if (static::$customCasterResolver) {
            $caster = static::$customCasterResolver->resolve($this->methodOrClass, $this->constructorArgs);
            // If the resolver returns a CasterInterface instance, wrap it in a closure
            if ($caster instanceof CasterInterface) {
                $cache->$cacheKey['instance'] = $caster;
                $caster                       = fn(mixed $value): mixed => $caster->cast($value, $args);
            }
            return $memoize($caster, static::$customCasterResolver::class, $this->methodOrClass);
        }

        // Step 5: Fail
        throw CastingException::unresolved($this->methodOrClass);
    }

    /**
     * 🐞 Debugging utility: retrieve internal memoized caster data.
     *
     * @param string|null $methodKey The method key to retrieve
     * @return \stdClass|array The memoized casters or a specific caster
     * @internal For debugging and introspection purposes only.
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function _getCasterMetadata(?string $methodKey = null): \stdClass|array|null
    {
        if ($methodKey !== null) {
            /** @var array */
            return self::$globalMemoizedCasters->$methodKey;
        }
        return self::$globalMemoizedCasters;
    }
    /** 🐞 Debugging utility: clear internal memoized caster data. */
    public static function _clearCasterMetadata(?string $methodKey = null): void
    {
        if ($methodKey !== null) {
            unset(self::$globalMemoizedCasters->{$methodKey});
        } else {
            self::$globalMemoizedCasters = new \stdClass;
        }
    }

    /**
     * Resolve the class name to a CasterInterface instance
     *
     * @param string $className The class name
     * @return CasterInterface The resolved instance
     */
    protected function resolveFromClass(string $className): CasterInterface
    {
        if (!is_subclass_of($className, CasterInterface::class)) {
            throw CastingException::casterInterfaceNotImplemented($className);
        }

        // If constructor args are provided, instantiate the class using them.
        if ($this->constructorArgs !== null) {
            // This will throw in case of signature mismatch.
            $instance = new $className(...$this->constructorArgs);

            if ($instance instanceof Injectable) $instance->inject();
            if ($instance instanceof Bootable) $instance->boot();

            return $instance;
        }

        $ref  = new \ReflectionClass($className);
        $ctor = $ref->getConstructor();
        // If no args are required, instantiate!
        if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
            $instance = $ref->newInstance();

            if ($instance instanceof Injectable) $instance->inject();
            if ($instance instanceof Bootable) $instance->boot();

            return $instance;
        }

        // ctorArgs needed but not provided: let DI container resolve it
        return $this->resolveWithContainer($className);
    }

    /**
     * Resolve the class name to a CasterInterface instance using a container.
     * To be overriden by the framework-specific implementation.
     *
     * @return CasterInterface
     */
    public function resolveWithContainer(string $className): CasterInterface
    {
        throw new \LogicException("Caster {$className} requires constructor args, but none were provided and no container is available.");
    }

    /**
     * Get an associative array of [propName => castingClosure] for a DTO
     *
     * @param \Nandan108\DtoToolkit\BaseDto $dto
     * @param bool $outbound
     * @return array
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getCastingClosureMap(BaseDto $dto, bool $outbound = false): array
    {
        static $cache = [];

        $reflection = new \ReflectionClass($dto);
        $dtoClass   = $reflection->getName();
        $casts      = &$cache[$dtoClass];

        if (!isset($casts)) {
            $casts = [0 => [], 1 => []];
            foreach ($reflection->getProperties() as $property) {
                $attributes = $property->getAttributes();
                foreach ($attributes as $attr) {
                    // only allow CastTo attributes or subclasses
                    $attrIsCasting = is_a($attr->getName(), self::class, true);
                    if (!$attrIsCasting) continue;

                    /** @var CastTo $instance */
                    $instance = $attr->newInstance();

                    $casts[(int)$instance->outbound][$property->getName()] = $instance->getCaster($dto);
                }
            }
        }

        return $casts[(int)$outbound];
    }
}
