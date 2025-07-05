<?php

namespace Nandan108\DtoToolkit;

use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\CasterResolverInterface;
use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;
use Nandan108\DtoToolkit\Internal\CasterMeta;

/**
 * Base class for all caster attributes.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class CastTo implements PhaseAwareInterface, CasterChainNodeProducerInterface
{
    use Traits\HasPhase;

    protected static string $methodPrefix = 'castTo';
    public static ?CasterResolverInterface $customCasterResolver = null;

    /** @var array<string, array{casters: array<string, CasterMeta>, instance?: CasterInterface}> */
    protected static array $globalMemoizedCasters = [];
    protected static array $injectables = [];

    protected static array $currentPropPath = [];
    protected static ?BaseDto $currentDto = null;

    /**
     * @internal a hook closure that is called when a cast is resolved
     *
     * @var \Closure(CasterMeta, bool): void
     */
    public static \Closure $onCastResolved;

    public function __construct(
        public ?string $methodOrClass = null,
        /** @psalm-suppress PossiblyUnusedProperty */
        public array $args = [],
        public ?array $constructorArgs = null,
    ) {
        if (!($this instanceof CasterInterface) && ($methodOrClass ?? '') === '') {
            throw new \LogicException('No casting method name or class provided.');
        }

        /** @psalm-suppress RedundantPropertyInitializationCheck */
        self::$onCastResolved ??= static function (): void {};
    }

    #[\Override]
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue = null): CasterMeta
    {
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $cache = &static::$globalMemoizedCasters;
        $args = $this->args;
        $this->methodOrClass ??= $this::class;
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $serialize = fn (mixed $args): string => json_encode($args) ?: '[]';

        /** @psalm-suppress RiskyTruthyFalsyComparison, PossiblyNullArgument */
        $getMemoizeKey = fn (string $keyType, BaseDto $dto): string => match ($keyType) {
            'class'      => ($this->methodOrClass ?? '').':'.$serialize($this->constructorArgs),
            'dto-method' => get_class($dto).'::'.static::$methodPrefix.ucfirst($this->methodOrClass),
        };

        // Check if we have a memoized caster for the given method
        foreach (['class', 'dto-method'] as $keyType) {
            $memoKey = $getMemoizeKey($keyType, $dto);
            $casterMeta = $cache[$memoKey]['casters'][$serialize($args)] ?? null;
            if ($casterMeta) {
                (static::$onCastResolved)($casterMeta, true);

                return $casterMeta;
            }
        }

        // Helper function to memoize the caster
        /**
         * @param string               $keyType  The type of key to use for memoization
         * @param \Closure|null        $caster   The caster closure to memoize
         * @param string|null          $object   The object or class name for debugging
         * @param string|null          $method   The method name for debugging
         * @param CasterInterface|null $instance The instance of the caster (if any)
         */
        $memoizeCaster = function (string $keyType, ?\Closure $caster = null, ?string $object = null, ?string $method = null, ?CasterInterface $instance = null) use (&$cache, $serialize, $args, $dto, $getMemoizeKey): CasterMeta {
            $memoKey = $getMemoizeKey($keyType, $dto);

            // CastInterface instances are memoized by class name, so we only keep one instance of each
            // if we're given an instance
            if ($instance) {
                $this->methodOrClass ??= $instance::class;
                // check if we've already got a memoized one, in which case use that one instead.
                $cachedInstance = $cache[$memoKey]['instance'] ?? null;
                // if this is a cache miss (we don't yet have an instance of that class)
                if (null === $cachedInstance) {
                    // put the instance in the cache
                    $cache[$memoKey]['instance'] = $instance;
                    // Then prepare it by injecting and booting
                    if ($instance instanceof Injectable) {
                        $instance->inject();
                    }
                    if ($instance instanceof Bootable) {
                        $instance->boot();
                    }
                }
            }

            $caster ??= match ($keyType) {
                'class'      => fn (mixed $value): mixed => $instance?->cast($value, $args),
                'dto-method' => fn (mixed $value): mixed => $dto->{$method}($value, ...$args),
            };

            // object and method are only useful for debugging
            $object ??= $this->methodOrClass;

            /** @psalm-suppress PossiblyNullArgument */
            $cache[$memoKey]['casters'][$serialize($args)] = $casterMeta = new CasterMeta(
                caster: $caster, // The actual transformation closure or callable
                instance: $instance, // The object behind the closure (if any)
                sourceClass: $object, // For debugging: where the caster came from
                sourceMethod: $method // Optional: method or other debug info
            );

            // echo "\nMemoizing caster: {$memoKey}";
            (static::$onCastResolved)($casterMeta, false);

            return $casterMeta;
        };

        // Check if we're using an Attribute Caster
        if ($this instanceof CasterInterface) {
            return $memoizeCaster(
                keyType: 'class',
                instance: $this,
            );
        }

        // A class name was provided? Resolve and use it.
        if (class_exists($this->methodOrClass)) {
            // if a class name is provided, we need to resolve it
            return $memoizeCaster(
                keyType: 'class',
                instance: $this->resolveFromClass($this->methodOrClass),
                object: $this->methodOrClass,
            );
        }

        // A DTO ?CastTo?+method-name was provided? Use it.
        $methodName = static::$methodPrefix.ucfirst($this->methodOrClass);
        if (method_exists($dto, method: $methodName)) {
            /** @psalm-suppress UnusedVariable */
            return $memoizeCaster(
                keyType: 'dto-method',
                object: $dto::class,
                method: $methodName,
            );
        }

        // Use a the custom resolver, if available.
        if (static::$customCasterResolver) {
            $caster = static::$customCasterResolver
                ->resolve($this->methodOrClass, $this->args, $this->constructorArgs ?? []);

            /** @var ?CasterInterface $instance */
            $instance = $caster instanceof CasterInterface ? $caster : null;
            /** @var ?\Closure $caster */
            $caster = $caster instanceof CasterInterface ? null : $caster;

            // If the resolver returns a CasterInterface instance, wrap it in a closure
            return $memoizeCaster(
                keyType: 'class',
                instance: $instance,
                caster: $caster,
                object: static::$customCasterResolver::class,
                method: $this->methodOrClass,
            );
        }

        throw CastingException::unresolved($this->methodOrClass);
    }

    /**
     * 🐞 Debugging utility: retrieve internal memoized caster data.
     *
     * @return array<string, array{casters: array<string, CasterMeta>, instance?: CasterInterface}> The memoized casters or a specific caster
     *
     * @internal for debugging and introspection purposes only
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function _getCasterMetadata(): array
    {
        return self::$globalMemoizedCasters;
    }

    /** 🐞 Debugging utility: clear internal memoized caster data. */
    public static function _clearCasterMetadata(?string $methodKey = null): void
    {
        if (null !== $methodKey) {
            unset(self::$globalMemoizedCasters[$methodKey]);
        } else {
            self::$globalMemoizedCasters = [];
        }
    }

    /**
     * Resolve the class name to a CasterInterface instance.
     *
     * @param string $className The class name
     *
     * @return CasterInterface The resolved instance
     */
    protected function resolveFromClass(string $className): CasterInterface
    {
        if (!is_subclass_of($className, CasterInterface::class)) {
            throw CastingException::casterInterfaceNotImplemented($className);
        }

        // If constructor args are provided, instantiate the class using them.
        if (null !== $this->constructorArgs) {
            // This will throw in case of signature mismatch.
            return new $className(...$this->constructorArgs);
        }

        $ref = new \ReflectionClass($className);
        $ctor = $ref->getConstructor();
        // If no args are required, instantiate!
        if (!$ctor || 0 === $ctor->getNumberOfRequiredParameters()) {
            return $ref->newInstance();
        }

        // ctorArgs needed but not provided: let DI container resolve it
        return $this->resolveWithContainer($className);
    }

    /**
     * Resolve the class name to a CasterInterface instance using a container.
     * To be overriden by the framework-specific implementation.
     */
    public function resolveWithContainer(string $className): CasterInterface
    {
        throw new \LogicException("Caster {$className} requires constructor args, but none were provided and no container is available.");
    }

    /**
     * Get an associative array of [propName => castingClosure] for a DTO.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getCastingClosureMap(BaseDto $dto, bool $outbound = false): array
    {
        /** @var array<class-string, array<int, array<string, CasterChain>>|null> */
        static $cache = [];
        self::$currentDto = $dto;
        $reflection = new \ReflectionClass($dto);
        $dtoClass = $reflection->getName();
        $casts = &$cache[$dtoClass];

        // static cache to keep track of which DTOs have been booted
        static $dtoBootCache = new \WeakMap();
        /** @var \WeakMap<BaseDto, true> $dtoBootCache */
        $phase = Phase::fromComponents($outbound, false);

        $phaseKey = (int) $outbound;
        if ($dto instanceof HasGroupsInterface) {
            /** @var array<string> */
            $activeGroups = $dto->getActiveGroups($phase);
            sort($activeGroups);
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $phaseKey .= ':'.(json_encode($activeGroups) ?: '');
        }

        // Populate the caster cache with per-phase-per-property composed casters
        if (!isset($casts[$phaseKey])) {
            $casts = [];

            $attrInstancesByProp = ($dto::class)::loadPhaseAwarePropMeta($phase, 'attr', CasterChainNodeProducerInterface::class);

            // build caster chains
            /** @var CasterChainNodeProducerInterface[]|CasterChainNodeProducerInterface $attrInstances
             * Note: All CasterChainNodeProducerInterface instances (Casters and chain modifiers)
             * are expected to be repeatable attributes. If one is not repeatable, $attrInstances won't be an array
             * and the instanciation of the CasterChain iterator will fail.
             */
            foreach (array_filter($attrInstancesByProp) as $propName => $attrInstances) {
                /** @psalm-suppress RedundantCondition */
                is_array($attrInstances) or ($msg = 'Attribute class '.get_class($attrInstances).
                    ' must be declared with #[Attribute(..., IS_REPEATABLE)].' and throw new \LogicException($msg));

                /** @var array<int, CasterChainNodeProducerInterface> $attrInstances */
                $iterator = new \ArrayIterator($attrInstances);
                $chain = new CasterChain($iterator, $dto);
                $casts[$phaseKey][$propName] = $chain;
            }
        }

        $map = $casts[$phaseKey] ?? [];

        // Boot chain elements on DTO (recursively), if needed
        if (!isset($dtoBootCache[$dto])) {
            // use a WeakMap for its object de-duplication properties
            /** @var \WeakMap<BootsOnDtoInterface, BootsOnDtoInterface> */
            $instances = new \WeakMap();
            // gather all instances of BootsOnDtoInterface, from all chains (both phases)
            foreach ($casts as $map) {
                foreach ($map as $chain) {
                    // ** @var CasterChain $chain */
                    $chain->recursiveWalk(function (CasterChainNodeInterface $meta) use ($instances): void {
                        if ($meta instanceof CasterMeta && $meta->instance instanceof BootsOnDtoInterface) {
                            if (!isset($instances[$meta->instance])) {
                                $instances[$meta->instance] = $meta->instance;
                            }
                        }
                    }, CasterMeta::class);
                }
            }
            // on each instance, run $instance->bootOnDto()
            foreach ($instances as $instance) {
                $instance->bootOnDto();
            }
            $dtoBootCache[$dto] = true;
        }

        // return the cast/ers for the requested phase
        return $map;
    }

    /**
     * Set the current casting property.
     *
     * @param string $propName The name of the property being cast
     */
    public static function setCurrentPropName(?string $propName): void
    {
        self::$currentPropPath = ($propName ?? '') ? [$propName] : [];
    }

    public static function pushPropPath(int|string $segment): void
    {
        self::$currentPropPath[] = $segment;
    }

    public static function popPropPath(): int|string|null
    {
        return array_pop(self::$currentPropPath);
    }

    public static function getPropPath(): ?string
    {
        $propPath = '';
        foreach (self::$currentPropPath as $segment) {
            $propPath .= is_int($segment) ? "[$segment]" : ".$segment";
        }

        return ltrim($propPath, '.') ?: null;
    }

    public static function setCurrentDto(?BaseDto $dto): void
    {
        self::$currentDto = $dto;
    }

    /**
     * Get the current $dto instance.
     *
     * @throws \RuntimeException if the $currentDto is not set
     *
     * @internal
     */
    public static function getCurrentDto(): BaseDto
    {
        // Can't use this trait without being a CastTo, but this should never happen under normal circumstances
        static::$currentDto or throw new CastingException('Casting context is not set: CastTo::$currentDto is null');

        return static::$currentDto;
    }

    /**
     * Get the name of the property currently being cast.
     *
     * @return non-empty-string
     *
     * @throws \RuntimeException if $currentPropName is not set
     *
     * @internal
     */
    public static function getCurrentPropName(): string
    {
        /** @var string $propName */
        $propName = static::$currentPropPath[0] ?? '';
        $propName > '' or throw new CastingException('Casting context is not set');

        /** @var non-empty-string $propName */
        return $propName;
    }
}
