<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\NodeResolverInterface;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\MissingDependencyException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

/**
 * Shared resolution logic for processing nodes (casters, validators, etc.).
 *
 * @psalm-api
 */
abstract class ProcessingNodeBase implements PhaseAwareInterface, ProcessingNodeProducerInterface
{
    use \Nandan108\DtoToolkit\Traits\HasPhase;

    protected static string $methodPrefix = '';

    /** @var class-string<ProcessingException> */
    protected string $exceptionType = ProcessingException::class;

    public static ?NodeResolverInterface $customNodeResolver = null;

    /** @var array<string, array{nodes: array<string, ProcessingNodeMeta>, instance?: ProcessingNodeInterface}> */
    protected static array $globalMemoized = [];

    public ?string $methodOrClass = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public array $args = [];

    public ?array $constructorArgs = null;

    /**
     * @param string|class-string|null $methodOrClass
     * @param mixed                    $constructorArgs
     *
     * @throws InvalidConfigException
     */
    public function __construct(?string $methodOrClass = null, array $args = [], ?array $constructorArgs = null)
    {
        $this->methodOrClass = $methodOrClass;
        $this->args = $args;
        $this->constructorArgs = $constructorArgs;

        if (($this->methodOrClass ?? '') === '') {
            throw new InvalidConfigException('No method name or class provided.');
        }
    }

    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue = null): ProcessingNodeMeta
    {
        $isDev = 'dev' === getenv('APP_ENV')
            || '1' === getenv('DEBUG')
            || 'cli' === php_sapi_name() && 'prod' !== getenv('APP_ENV');

        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $cache = &static::$globalMemoized;
        $args = $this->args;
        $this->methodOrClass ??= $this::class;
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (\is_array($value)) {
                $normalized = [];
                foreach ($value as $key => $item) {
                    $normalized[$key] = $normalize($item);
                }

                return $normalized;
            }

            if ($value instanceof \BackedEnum) {
                return ['__enum__' => $value::class, 'value' => $value->value];
            }

            if ($value instanceof \UnitEnum) {
                return ['__enum__' => $value::class, 'name' => $value->name];
            }

            if ($value instanceof \DateTimeInterface) {
                return ['__datetime__' => $value::class, 'value' => $value->format(\DateTimeInterface::ATOM)];
            }

            if (\is_object($value)) {
                return ['__object__' => $value::class, 'id' => spl_object_id($value)];
            }

            if (\is_resource($value)) {
                return ['__resource__' => get_resource_type($value)];
            }

            return $value;
        };

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $serialize = fn (mixed $args): string => json_encode($normalize($args)) ?: '[]';

        /** @psalm-suppress RiskyTruthyFalsyComparison, PossiblyNullArgument */
        $getMemoizeKey = function (string $keyType, BaseDto $dto) use ($isDev, $serialize): string {
            $key = match ($keyType) {
                'class'      => ($this->methodOrClass ?? '').':'.$serialize($this->constructorArgs),
                'dto-method' => get_class($dto).'::'.static::$methodPrefix.ucfirst($this->methodOrClass),
            };

            return $isDev ? $key : hash('xxh3', $key);
        };

        // Check if we have a memoized callable for the given method
        foreach (['class', 'dto-method'] as $keyType) {
            $memoKey = $getMemoizeKey($keyType, $dto);
            $meta = $cache[$memoKey]['nodes'][$serialize($args)] ?? null;
            if ($meta) {
                return $meta;
            }
        }

        /**
         * @param string                               $keyType      The type of key to use for memoization
         * @param \Closure(object $instance): \Closure $makeCallable A closure that makes the callable to memoize
         * @param string                               $object       The object or class name for debugging
         * @param string                               $method       The method name for debugging
         * @param object|class-string|null             $instance     The instance backing the callable (if any)
         */
        $memoize = function (
            ?string $object = null,
            ?string $method = null,
            object | string | null $instance = null,
        ) use (&$cache, $serialize, $args, $dto, $getMemoizeKey): ProcessingNodeMeta {
            /** @psalm-suppress RiskyTruthyFalsyComparison // class-string should never be empty... but whatever! */
            $keyType = $instance ? 'class' : 'dto-method';
            $memoKey = $getMemoizeKey($keyType, $dto);

            if (null === $instance) {
                // A DTO method? Use it.
                /** @var string $method */
                $callable = $this->makeClosureFromDtoMethod($dto, $method, $args);
            } else {
                // A class name was provided? Resolve and use it.
                if (is_string($instance)) {
                    $this->methodOrClass = $instance;
                }

                $this->methodOrClass ??= $instance::class;
                $cachedInstance = $cache[$memoKey]['instance'] ?? null;
                if (null === $cachedInstance) {
                    if (is_string($instance)) {
                        /** @psalm-suppress ArgumentTypeCoercion */
                        $instance = $this->resolveFromClass($instance);
                    }
                    $cache[$memoKey]['instance'] = $instance;
                    if ($instance instanceof Injectable) {
                        $instance->inject();
                    }
                    if ($instance instanceof Bootable) {
                        $instance->boot();
                    }
                } else {
                    $instance = $cachedInstance;
                }

                $callable = $this->makeClosureFromInstance($instance, $args);
            }

            $object ??= $this->methodOrClass;

            /** @psalm-suppress PossiblyNullArgument */
            $cache[$memoKey]['nodes'][$serialize($args)] = $meta = new ProcessingNodeMeta(
                callable: $callable,
                instance: $instance,
                sourceClass: $object,
                sourceMethod: $method,
            );

            return $meta;
        };

        // Attribute instance implements the interface directly
        $expectedInterface = $this->getInterface();
        if ($this instanceof $expectedInterface) {
            return $memoize(instance: $this);
        }

        // A class name was provided? Resolve and use it.
        if (class_exists($this->methodOrClass)) {
            return $memoize(instance: $this->methodOrClass, object: $this->methodOrClass);
        }

        // A DTO method? Use it.
        $methodName = static::$methodPrefix.ucfirst($this->methodOrClass);
        if (method_exists($dto, method: $methodName)) {
            return $memoize(object: $dto::class, method: $methodName);
        }

        if (!static::$customNodeResolver) {
            throw NodeProducerResolutionException::for($this->methodOrClass);
        }

        $resolved = static::$customNodeResolver
            ->resolve($this->methodOrClass, args: $this->args, constructorArgs: $this->constructorArgs ?? []);

        /** @var ?ProcessingNodeInterface $instance */
        $instance = $resolved instanceof CasterInterface || $resolved instanceof ValidatorInterface ? $resolved : null;
        /** @var ?\Closure $callable */
        $callable = $instance ? null : $resolved;

        if (!$callable) {
            $instance or throw NodeProducerResolutionException::for($this->methodOrClass);
            $callable = $this->makeClosureFromInstance($instance, $this->args);
        }

        $memoKey = $this->methodOrClass.':'.$serialize($this->constructorArgs);
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $cache = &static::$globalMemoized;
        $cache[$memoKey]['instance'] ??= $instance;
        $cache[$memoKey]['nodes'][$serialize($this->args)] = $meta = new ProcessingNodeMeta(
            callable: $callable,
            instance: $cache[$memoKey]['instance'] ?? $instance,
            sourceClass: static::$customNodeResolver::class,
            sourceMethod: $this->methodOrClass,
        );

        return $meta;
    }

    /**
     * Resolve the class name to an instance of the expected interface.
     *
     * @param class-string $className
     */
    protected function resolveFromClass(string $className): object
    {
        $interface = $this->getInterface();
        if (!is_subclass_of($className, $interface)) {
            throw $this->interfaceError($className);
        }

        if (null !== $this->constructorArgs) {
            return new $className(...$this->constructorArgs);
        }

        $ref = new \ReflectionClass($className);
        $ctor = $ref->getConstructor();
        if (!$ctor || 0 === $ctor->getNumberOfRequiredParameters()) {
            return $ref->newInstance();
        }

        return $this->resolveWithContainer($className);
    }

    /**
     * Get an associative array of [propName => processingClosure] for a DTO.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getProcessingNodeClosureMap(BaseDto $dto, bool $outbound = false): array
    {
        /** @var array<class-string, array<int, array<string, ProcessingChain>>|null> */
        static $cache = [];
        $reflection = new \ReflectionClass($dto);
        $dtoClass = $reflection->getName();
        $processors = &$cache[$dtoClass];

        // static cache to keep track of which DTOs have been booted
        static $dtoBootCache = new \WeakMap();
        /** @var \WeakMap<BaseDto, true> $dtoBootCache */
        $phase = Phase::fromComponents($outbound, false);

        $phaseKey = (int) $outbound;
        if ($dto instanceof HasGroupsInterface) {
            $activeGroups = $dto->getActiveGroups($phase);
            sort($activeGroups);
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $phaseKey .= ':'.(json_encode($activeGroups) ?: '');
        }

        // Populate the caster cache with per-phase-per-property composed casters
        if (!isset($processors[$phaseKey])) {
            $processors = [];

            $attrInstancesByProp = ($dto::class)::getPhaseAwarePropMeta($phase, 'attr', ProcessingNodeProducerInterface::class);

            // build caster chains
            /** @var ProcessingNodeProducerInterface[]|ProcessingNodeProducerInterface $attrInstances
             * Note: All ProcessingNodeProducerInterface instances (Casters and chain modifiers)
             * are expected to be repeatable attributes. If one is not repeatable, $attrInstances won't be an array
             * and the instanciation of the ProcessingChain iterator will fail.
             */
            foreach (array_filter($attrInstancesByProp) as $propName => $attrInstances) {
                /** @psalm-suppress RedundantCondition */
                is_array($attrInstances) or ($msg = 'Attribute class '.get_class($attrInstances).
                    ' must be declared with #[Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)].' and throw new InvalidConfigException($msg));

                /** @var array<int, ProcessingNodeProducerInterface> $attrInstances */
                $iterator = new \ArrayIterator($attrInstances);
                $chain = new ProcessingChain($iterator, $dto);
                $processors[$phaseKey][$propName] = $chain;
            }
        }

        $map = $processors[$phaseKey] ?? [];

        // Boot chain elements on DTO (recursively), if needed
        if (!isset($dtoBootCache[$dto])) {
            // use a WeakMap for its object de-duplication properties
            /** @var \WeakMap<BootsOnDtoInterface, BootsOnDtoInterface> */
            $instances = new \WeakMap();
            // gather all instances of BootsOnDtoInterface, from all chains (both phases)
            foreach ($processors as $map) {
                foreach ($map as $chain) {
                    // ** @var ProcessingChain $chain */
                    $chain->recursiveWalk(function (ProcessingNodeInterface $meta) use ($instances): void {
                        if ($meta instanceof ProcessingNodeMeta && $meta->instance instanceof BootsOnDtoInterface) {
                            if (!isset($instances[$meta->instance])) {
                                $instances[$meta->instance] = $meta->instance;
                            }
                        }
                    }, ProcessingNodeMeta::class);
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
     * 🐞 Debugging utility: retrieve internal memoized caster data.
     *
     * @return array<string, array{nodes: array<string, ProcessingNodeMeta>, instance?: ProcessingNodeInterface}> The memoized casters or a specific caster
     *
     * @internal for debugging and introspection purposes only
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function _getNodeMetadata(): array
    {
        return self::$globalMemoized;
    }

    /** 🐞 Debugging utility: clear internal memoized caster data. */
    public static function _clearNodeMetadata(?string $methodKey = null): void
    {
        if (null !== $methodKey) {
            unset(self::$globalMemoized[$methodKey]);
        } else {
            self::$globalMemoized = [];
        }
    }

    /**
     * Utility function: Check if the value is stringable.
     */
    protected function is_stringable(mixed $val): bool
    {
        return is_string($val)
            || is_numeric($val)
            || is_object($val) && method_exists($val, '__toString');
    }

    /** @return class-string */
    abstract protected function getInterface(): string;

    abstract protected function makeClosureFromInstance(object $instance, array $args): \Closure;

    abstract protected function makeClosureFromDtoMethod(BaseDto $dto, string $method, array $args): \Closure;

    abstract protected function interfaceError(string $class): \Throwable;

    abstract public function resolveWithContainer(string $className): object;

    protected function ensureStringable(
        mixed $value,
        bool $expectNonEmpty = false,
    ): string {
        $exceptionType = $this->exceptionType;

        if ($this->is_stringable($value)) {
            $value = (string) $value;

            if ($expectNonEmpty && '' === trim($value)) {
                throw $exceptionType::expectedStringable(
                    static::class,
                    $value,
                    true, // non empty
                );
            }

            return $value;
        }

        throw $exceptionType::expectedStringable(
            static::class,
            $value,
            $expectNonEmpty,
        );
    }

    protected function ensureExtensionLoaded(string $extensionName): void
    {
        $loaded = extension_loaded($extensionName);
        // compact syntax allows full test coverage even when extension is present (failing case impossible to cover)
        $loaded or throw new MissingDependencyException($extensionName, static::class);
    }
}
