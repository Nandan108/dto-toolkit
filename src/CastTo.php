<?php

namespace Nandan108\DtoToolkit;

use Attribute;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\CasterResolverInterface;
use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Support\CasterChainBuilder;

/**
 * Base class for all caster attributes
 *
 * @psalm-api
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CastTo
{
    static protected string $methodPrefix = 'castTo';
    public static ?CasterResolverInterface $customCasterResolver = null;
    protected static ?\stdClass $globalMemoizedCasters = null;

    /**
     * @internal A hook closure that is called when a cast is resolved.
     * @var \Closure(array $casterMeta, bool $isCacheHit): void
     */
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
    public function getCaster(BaseDto $dto): \Closure
    {
        $serialize           = fn(mixed $data, string $prefix           = ''): string => $data ? $prefix . serialize($data) : 'null';
        $cache               = static::$globalMemoizedCasters;
        $args                = $this->args;
        $serializedArgs      = $serialize($args);
        $this->methodOrClass ??= $this::class;
        $cacheKey            = $this->methodOrClass . $serialize($this->constructorArgs, ':');

        // early return on cache hit
        $casterMeta = $cache->$cacheKey['casters'][$serializedArgs] ?? null;
        if ($casterMeta !== null) {
            (static::$onCastResolved)($casterMeta, true);
            return $casterMeta['caster'];
        }

        /** @var ?CasterInterface $instance */
        $instance = null;

        // Helper function to memoize the caster
        $memoizeCaster   = function (\Closure $caster   = null, ?string $object   = null, string $method   = null) use (&$cache, &$cacheKey, $serializedArgs, &$instance, $args): \Closure {
            $caster ??= fn(mixed $value): mixed => $instance?->cast($value, $args);
            $object ??= $this->methodOrClass;
            $method ??= static::$methodPrefix . ucfirst($this->methodOrClass ?? '');
            $cache->$cacheKey['casters'][$serializedArgs] = $casterMeta = [
                'caster' => $caster,
                'object' => $object,
                'method' => $method,
            ];
            (static::$onCastResolved)($casterMeta, false);
            return $caster;
        };
        $memoizeInstance = function (CasterInterface $casterInstance) use ($cache, $cacheKey, &$instance): void {
            $this->methodOrClass = $casterInstance::class;
            $instance = $cache->$cacheKey['instance'] ??= $casterInstance;
        };

        // Check if we're using an Attribute Caster
        if ($this instanceof CasterInterface) {
            $memoizeInstance($this);
            return $memoizeCaster();
        }

        // Throw if no method or class name was provided
        if ($this->methodOrClass === '') {
            throw new \LogicException('No casting method name or class provided.');
        }

        // A class name was provided? Resolve and use it.
        if (class_exists($this->methodOrClass)) {
            // if a class name is provided, we need to resolve it
            $memoizeInstance($this->resolveFromClass($this->methodOrClass));
            return $memoizeCaster();
        }

        // A DTO 'CastTo'+method-name was provided? Use it.
        $methodName = static::$methodPrefix . ucfirst($this->methodOrClass);
        if (method_exists($dto, $methodName)) {
            return $memoizeCaster(
                caster: fn(mixed $value): mixed => $dto->{$methodName}($value, ...$args),
                object: $dto::class,
                method: $methodName,
            );
        }

        // Use a the custom resolver, if available.
        if (static::$customCasterResolver) {
            $caster = static::$customCasterResolver
                ->resolve($this->methodOrClass, $this->constructorArgs);
            if ($caster instanceof CasterInterface) {
                // If the resolver returns a CasterInterface instance, wrap it in a closure
                $memoizeInstance($caster);
                $caster = fn(mixed $value): mixed => $caster->cast($value, $args);
            } else {
                // If the resolver returns a closure, use it directly
            }
            return $memoizeCaster(
                caster: $caster,
                object: static::$customCasterResolver::class,
                method: $this->methodOrClass,
            );
        }

        throw CastingException::unresolved($this->methodOrClass);
    }

    /**
     * @return bool True if the modifier applies to outbound casting, false otherwise.
     */
    public function isOutbound(): bool
    {
        return $this->outbound;
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
     * @param \Nandan108\DtoToolkit\Core\BaseDto $dto
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

        // Populate the caster cache with per-phase-per-property composed casters
        if (!isset($casts)) {
            $casts = [];

            foreach ($reflection->getProperties() as $property) {
                $propName   = $property->getName();
                $attributes = $property->getAttributes();

                $casterAttrByPhase = [0 => [], 1 => []];

                // instantiate the attributes
                $attrInstances = array_map(fn($attr) => $attr->newInstance(), $attributes);
                // filter out the ones that are not CastTo or CastModifier
                $attrInstances = array_filter($attrInstances, fn($attr) => $attr instanceof CastTo || $attr instanceof CastModifierInterface);

                // separate into inbound and outbound chains
                foreach ($attrInstances as $attrInstance) {
                    $casterAttrByPhase[(int)$attrInstance->isOutbound()][] = $attrInstance;
                }

                // build the chain for each phase
                foreach ($casterAttrByPhase as $phase => $attrInstances) {
                    if ($attrInstances) {
                        $casts[$phase][$propName] = CasterChainBuilder::buildCasterChain($attrInstances,$dto);
                    }
                }
            }
        }
        // return the casters for the requested phase
        return $casts[$outbound] ?? [];
    }
}
