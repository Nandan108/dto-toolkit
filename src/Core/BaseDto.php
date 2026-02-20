<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\Presence;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ProvidesProcessingNodeNameInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Support\ContainerBridge;

/**
 * @method static static newWithErrorMode(ErrorMode $mode)
 *
 * @psalm-type DtoPropMetaCache = array{
 *     defaultValue: array<truthy-string, mixed>,
 *     propRef: array<truthy-string, \ReflectionProperty>,
 *     presencePolicy:  array<truthy-string, PresencePolicy>,
 *     nodeName?: ?truthy-string,
 *     classRef?: \ReflectionClass<static>,
 *     phase?: array<non-empty-string, array<truthy-string, PhaseAwareInterface|list<PhaseAwareInterface>>>,
 * }
 */
abstract class BaseDto implements ProvidesProcessingNodeNameInterface
{
    protected static ErrorMode $_defaultErrorMode = ErrorMode::FailFast;
    protected ?ErrorMode $_errorMode = null;
    protected ?ProcessingErrorList $_errorList = null;

    /**
     * List of properties that can be filled.
     *
     * @var list<truthy-string>
     **/
    protected ?array $_fillable = null;

    /**
     * List of properties that have been filled.
     * The key is the property name, and the value is ALWAYS true.
     * An unfilled property is simply not present in this array.
     *
     * @var array<truthy-string, true>
     **/
    public array $_filled = [];

    /**
     * Full metadata cache per class (static).
     *
     * @psalm-import-type DtoPropMetaCache from self
     *
     * @psalm-var array<class-string, DtoPropMetaCache>
     */
    private static array $_dtoMetaCache = [];

    /** @api */
    public static function clearAllCaches(): void
    {
        self::$_dtoMetaCache = [];
        ProcessingNodeBase::_clearNodeMetadata();
    }

    /** @api */
    public static function setDefaultErrorMode(ErrorMode $mode): void
    {
        static::$_defaultErrorMode = $mode;
    }

    /** @api */
    public function getErrorMode(): ErrorMode
    {
        return $this->_errorMode ?? static::$_defaultErrorMode;
    }

    // /** @psalm-suppress PossiblyUnusedMethod */
    /** @api */
    public function withErrorMode(ErrorMode $mode): static
    {
        $this->_errorMode = $mode;

        return $this;
    }

    /** @api */
    public function setErrorList(ProcessingErrorList $newErrorList): ProcessingErrorList
    {
        return $this->_errorList = $newErrorList;
    }

    /** @api */
    public function getErrorList(): ProcessingErrorList
    {
        return $this->_errorList ??= new ProcessingErrorList();
    }

    /**
     * Initialize phase-agnostic property metadata for a DTO class.
     * For each prop, gathers :
     * - \ReflectionProperty
     * - default value
     * - presence policy.
     *
     * @param class-string $class
     */
    private static function initPropMeta(string $class): void
    {
        self::$_dtoMetaCache[$class] ??= [
            'defaultValue'                 => [],
            'propRef'                      => [],
        ];
        $refClass = static::getClassRef();

        foreach ($refClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            /** @var truthy-string */
            $propName = $prop->getName();

            // Ignore static properties
            if ($prop->isStatic() || '_' === $propName[0]) {
                continue;
            }

            self::$_dtoMetaCache[$class]['propRef'][$propName] = $prop;

            if ($prop->hasDefaultValue()) {
                /** @psalm-var mixed */
                self::$_dtoMetaCache[$class]['defaultValue'][$propName] = $prop->getDefaultValue();
            } else {
                throw new InvalidConfigException("Default value missing on DTO property: {$class}::\${$propName}.");
            }
        }

        // Initialize policy-related property metadata for a DTO class.

        $dtoDefaultPolicy = PresencePolicy::Default;

        // DTO-level attribute
        if ($attr = $refClass->getAttributes(Presence::class)[0] ?? null) {
            $dtoDefaultPolicy = $attr->newInstance()->policy;
        }

        /** @psalm-var array<string, PresencePolicy> $resolved */
        $resolved = [];

        foreach (self::$_dtoMetaCache[$class]['propRef'] as $propName => $propRef) {
            $policy = $dtoDefaultPolicy;

            // Property-level override
            if ($attr = $propRef->getAttributes(Presence::class)[0] ?? null) {
                $policy = $attr->newInstance()->policy;
            }

            $resolved[$propName] = $policy;
        }
        /** @var array<truthy-string, PresencePolicy> $resolved */
        self::$_dtoMetaCache[$class]['presencePolicy'] = $resolved;
    }

    /**
     * @psalm-return DtoPropMetaCache
     */
    protected static function getPropMeta(): array
    {
        $class = static::class;

        if (!isset(self::$_dtoMetaCache[$class]['propRef'])) {
            self::initPropMeta($class);
        }

        return self::$_dtoMetaCache[$class];
    }

    /** @return array<truthy-string, \ReflectionProperty> */
    protected static function getPropRefs(): array
    {
        return static::getPropMeta()['propRef'];
    }

    /**
     * @return array<truthy-string, mixed> a map of default values for the DTO properties
     *
     * @api
     */
    public static function getDefaultValues(): array
    {
        return static::getPropMeta()['defaultValue'];
    }

    /**
     * Get a map of presence policies for the DTO properties.
     *
     * @return array<truthy-string, PresencePolicy>
     *
     * @api
     */
    public static function getPresencePolicy(): array
    {
        return static::getPropMeta()['presencePolicy'];
    }

    private static function initPhaseAwarePropMeta(): void
    {
        $cache = [];

        foreach (static::getPropRefs() as $propName => $propRef) {
            $isOutbound = false;

            $attributes = $propRef->getAttributes();

            foreach (Phase::cases() as $phaseCase) {
                $cache[$phaseCase->value][$propName] = [];
            }

            foreach ($attributes as $attributeRef) {
                $attrInstance = $attributeRef->newInstance();
                /** @var class-string $attributeClass */
                $attributeClass = $attributeRef->getName();
                /** @var int $flags */
                $flags = (new \ReflectionClass($attributeClass))->getAttributes()[0]->newInstance()->flags;
                $isRepeatable = ($flags & \Attribute::IS_REPEATABLE) === \Attribute::IS_REPEATABLE;

                if ($attrInstance instanceof Outbound) {
                    $isOutbound = true;
                    continue;
                }

                if ($attrInstance instanceof PhaseAwareInterface) {
                    $attrInstance->setOutbound($isOutbound);
                    if ($isRepeatable) {
                        /** @var array<truthy-string, array<truthy-string, array{attr?: list<PhaseAwareInterface>}>> $cache */
                        $cache[$attrInstance->getPhase()->value][$propName]['attr'][] = $attrInstance;
                    } else {
                        $cache[$attrInstance->getPhase()->value][$propName]['attr'] = $attrInstance;
                    }
                }
            }
        }

        /** @psalm-var array<truthy-string, array<truthy-string, PhaseAwareInterface|list<PhaseAwareInterface>>> $cache */
        self::$_dtoMetaCache[static::class]['phase'] = $cache;
    }

    /**
     * Load the metadata for the given phase.
     *
     * @param \Closure|class-string|null $filter
     *
     * @return array<non-empty-string, PhaseAwareInterface|list<PhaseAwareInterface>>
     *
     * @api
     */
    public static function getPhaseAwarePropMeta(Phase $phase, string $metaDataName, \Closure | string | null $filter, bool $ignoreEmpty = false): array
    {
        if (!isset(self::$_dtoMetaCache[static::class]['phase'])) {
            self::initPhaseAwarePropMeta();
        }

        // get the metadata for the given phase
        /** @var array<non-empty-string, mixed> $meta */
        $meta = self::$_dtoMetaCache[static::class]['phase'][$phase->value] ?? [];

        // extract the relevant metadata for the given metadata name (e.g. 'attr')
        /** @var array<non-empty-string, PhaseAwareInterface|list<PhaseAwareInterface>> $metaByName */
        $metaByName = array_map(
            static function (array $propMeta) use ($metaDataName) {
                return $propMeta[$metaDataName] ?? [];
            },
            $meta,
        );

        // if filter is null, we keep all attributes
        if (null !== $filter) {
            /** @var callable(mixed): mixed */
            $filterClosure = is_string($filter)
                // if $filter is a string, it's an class name, and we keep only instances of that class
                ? fn (object $attr): bool => $attr instanceof $filter
                : $filter;

            /** @var mixed $meta */
            foreach ($metaByName as &$meta) {
                // /** @psalm-suppress TooManyArguments */
                if (is_array($meta)) {
                    $meta = array_filter($meta, $filterClosure);
                } elseif (!$filterClosure($meta)) {
                    $meta = []; // if not an array, filter it and return null if empty
                }
            }
        }

        // if ignoreEmpty is false, return all attributes as-is
        if ($ignoreEmpty) {
            $metaByName = array_filter($metaByName, static fn (mixed $meta): bool => (bool) $meta);
        }

        return $metaByName;
    }

    /**
     * Get the DTO data content as an array.
     * If the DTO implements NormalizesOutboundInterface, the casters (CastTo Attributes)
     * declared after #[Outbound] will be called to transform the data before returning it.
     *
     * @psalm-suppress PossiblyUnusedMethod, UnusedParam, InvalidReturnType
     *
     * @return array<truthy-string, mixed>
     *
     * @api
     */
    public function toOutboundArray(
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $runPreOutputHook = true,
        bool $applyOutboundMappings = true,
    ): array {
        if ($this instanceof ScopedPropertyAccessInterface) {
            // If the DTO implements ScopedPropertyAccessInterface, we only export properties in scope.
            // This is useful for DTOs that have different scopes for different phases.
            $propsInScope = $this->getPropertiesInScope(Phase::OutboundExport);
            $propsInScope = array_intersect($propsInScope, array_keys($this->_filled));
        } else {
            // If the DTO does not implement ScopedPropertyAccessInterface, we export all filled properties.
            $propsInScope = null; // all properties
        }

        $data = $this->toArray($propsInScope);

        // Apply any #[MapTo] attributes (map to outbound names)
        if ($applyOutboundMappings) {
            $data = MapTo::applyOutboundKeys($data, $this);
        }

        if ($this instanceof ProcessesInterface) {
            $data = $this->processOutbound($data, $errorList, $errorMode);
        }

        if ($runPreOutputHook) {
            $this->preOutput($data);
        }

        /** @var array<truthy-string, mixed> $data */
        return $data;
    }

    /**
     * Get the fillable properties of the DTO.
     * These are public instance properties, excluding those prefixed with "_".
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @return list<truthy-string>
     *
     * @api
     */
    public function getFillable(): array
    {
        // Get the names of static's public properties, excluding those prefixed with "_".
        return $this->_fillable ??= array_keys(static::getPropRefs());
    }

    /**
     * Check if the given property has been filled.
     *
     * @api
     */
    public function isFilled(string $propName): bool
    {
        return $this->_filled[$propName] ?? false;
    }

    /**
     * Get DTO properties corresponding to the given property names as an array.
     * If no property names are given, all public, filled properties will be returned.
     * The data is returned as-is, without any transformation.
     *
     * @param ?array<non-empty-string> $propNames
     *
     * @return array<truthy-string, mixed>
     *
     * @api
     */
    public function toArray(?array $propNames = null): array
    {
        $vars = get_object_vars($this);
        $keys = $propNames ?? array_keys($this->_filled);

        /** @var array<truthy-string, mixed> */
        return array_intersect_key($vars, array_flip($keys));
    }

    /**
     * Reset public properties (all or specified) to their default values.
     * Properties prefixed with "_" are considered internal and are not cleared.
     *
     * This method is useful to prepare the DTO for safe reuse.
     *
     * @param list<truthy-string>  $propNames        specific property names to clear. If empty, all public props are cleared.
     * @param array<string, mixed> $excludedPropsMap a property-name keyed map. These props will NOT be cleared.
     * @param bool                 $clearErrors      whether to clear DTO's errorList
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function clear(array $propNames = [], array $excludedPropsMap = [], bool $clearErrors = true): static
    {
        $defaultValues = static::getDefaultValues();

        // If specific prop names are given, restrict to those
        if ($propNames) {
            $defaultValues = array_intersect_key($defaultValues, array_flip($propNames));
        }
        // If excluded props are given, remove them from the defaults to be set
        if ($excludedPropsMap) {
            $defaultValues = array_diff_key($defaultValues, $excludedPropsMap);
        }

        /** @psalm-var mixed $defaultValue */
        foreach ($defaultValues as $propName => $defaultValue) {
            $this->$propName = $defaultValue;
            unset($this->_filled[$propName]);
        }

        if ($clearErrors) {
            $this->getErrorList()->clear();
        }

        return $this;
    }

    /**
     * Fill the DTO with the given values.
     *
     * This method will set the value of given properties and mark them as filled.
     * This means that they will be included in further processing such as
     * validation, normalization, export, or entity mapping.
     * Null values are allowed and will be treated as filled.
     *
     * Note: '_' prefixed properties are ignored
     *
     * @param array<truthy-string, mixed> $values
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function fill(array $values): static
    {
        /** @psalm-var mixed $value */
        foreach ($values as $key => $value) {
            $this->$key = $value;
            $this->_filled[$key] = true;
        }

        return $this;
    }

    /**
     * Unmarks the given properties as filled.
     *
     * This does not modify the current values of the properties,
     * but they will be excluded from further processing such as
     * normalization, export, or entity mapping.
     *
     * @param ?list<truthy-string> $props
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function unfill(?array $props = null): static
    {
        if (null === $props) {
            $this->_filled = [];
        } else {
            foreach ($props as $key) {
                unset($this->_filled[$key]);
            }
        }

        return $this;
    }

    /**
     * Post-load hook.
     *
     * This method is a hook for subclasses to implement any
     * additional logic after the DTO has been loaded with data.
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function postLoad()
    {
        // no-op - to be implemented in subclasses
    }

    /**
     * Pre-output hook.
     *
     * This method is a hook for subclasses to implement any additional
     * logic before an Entity, array, Model, or other output is returned.
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod
     * @psalm-suppress PossiblyUnusedParam
     *
     * @api
     */
    public function preOutput(array | object &$outputData)
    {
        // no-op - to be implemented in subclasses
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     *
     * @internal
     **/
    public static function __callStatic(string $method, array $arguments): static
    {
        $ref = static::getClassRef();
        /** @var array<string, \ReflectionMethod> $resolvedMethods */
        static $resolvedMethods = [];
        /** @psalm-suppress  UnsupportedReferenceUsage */
        /** @var ?\ReflectionMethod $resolvedInstanceMethod */
        $resolvedInstanceMethod = &$resolvedMethods[$ref->name.'::'.$method] ?? null;

        if (!$resolvedInstanceMethod) {
            $prefix = substr($method, 0, 7);
            if (in_array($prefix, ['newFrom', 'newWith'], true)) {
                $instanceMethodName = match ($prefix) {
                    'newFrom' => 'load',
                    'newWith' => 'with',
                }.substr($method, 7);
                $resolvedInstanceMethod = self::getValidMethodRef($instanceMethodName, $ref);

            } else {
                // if __callStatic was called, it means the method doesn't exist, therefore getValidMethodRef() will throw.
                self::getValidMethodRef($method, $ref, false);
            }
        }

        // make a new instance of the class
        $instance = static::new();
        // call the method on the instance
        /** @var \ReflectionMethod $resolvedInstanceMethod */
        $resolvedInstanceMethod->invoke($instance, ...$arguments);

        return $instance;
    }

    /**
     * Create a new instance of the DTO.
     * This method will use the container to create the instance if the class is marked with #[Inject].
     * If the class is not marked with #[Inject], it will create a new instance using `new static()`.
     *
     * This method will also call the `inject()` method if the class implements Injectable,
     * and the `boot()` method if the class implements Bootable.
     *
     * @api
     */
    public static function new(): static
    {
        /** @var bool[] $isInjectable */
        static $isInjectable = [];

        // we should cache all relevant attributes in a static array

        $classRef = static::getClassRef();

        $shouldUseContainer = $isInjectable[static::class] ??=
            (bool) $classRef->getAttributes(Inject::class);

        // make a new instance of the class,
        /** @psalm-suppress UnsafeInstantiation */
        /** @var static $instance */
        $instance = $shouldUseContainer
            // via container injection if the class is marked with #[Inject],
            ? ContainerBridge::get(static::class)
            // otherwise with a simple new static()
            : new static();

        // prepare the instance for use
        if ($instance instanceof Injectable) {
            $instance->inject();
        }
        if ($instance instanceof Bootable) {
            $instance->boot();
        }

        $defaultGroupsRef = $classRef->getAttributes(WithDefaultGroups::class)[0] ?? null;
        if ($defaultGroupsRef) {
            $defaultGroupsRef->newInstance()->applyToDto($instance);
        }

        /** @var static $instance */
        return $instance;
    }

    /** @internal */
    public static function getClassRef(): \ReflectionClass
    {
        $ref = static::$_dtoMetaCache[static::class]['classRef'] ?? null;
        if (null === $ref) {
            $ref = new \ReflectionClass(static::class);
            $constructorRef = $ref->getConstructor();
            if ($constructorRef && $constructorRef->getNumberOfRequiredParameters() > 0) {
                throw new InvalidConfigException("DTO class {$ref->getName()} has a constructor with required parameters. Please make sure the constructor can be called without arguments.");
            }
            /** @psalm-suppress PropertyTypeCoercion */
            static::$_dtoMetaCache[static::class]['classRef'] = $ref;
        }

        return $ref;
    }

    protected static function getValidMethodRef(string $method, \ReflectionClass $classRef, bool $visible = true): \ReflectionMethod
    {
        $className = $classRef->getName();
        if (!$classRef->hasMethod($method)) {
            throw new InvalidConfigException("Method $className::{$method}() does not exist.");
        }
        $methodRef = $classRef->getMethod($method);
        $visibility = match (true) {
            $methodRef->isPublic()    => 'public',
            $methodRef->isProtected() => 'protected',
            default                   => 'private',
        };
        if (!$visible || $methodRef->isPrivate()) {
            throw new InvalidConfigException(ucfirst($visibility)." method $className::{$method}() is not reachable from calling context.");
        }

        return $methodRef;
    }

    #[\Override]
    /** @return truthy-string */
    /** @api */
    public function getProcessingNodeName(): string
    {
        // make sure we initialize the prop meta cache
        static::getPropMeta();
        /** @psalm-suppress UnsupportedPropertyReferenceUsage, PossiblyUndefinedArrayOffset */
        $nodeName = &static::$_dtoMetaCache[static::class]['nodeName'];

        if (null === $nodeName) {
            if (ProcessingContext::isDevMode()) {
                $ref = static::getClassRef();
                // /** @psalm-suppress PropertyTypeCoercion */
                $nodeName = $ref->isAnonymous()
                    ? 'AnonymousDTO('.basename($ref->getFileName()).":{$ref->getStartLine()})"
                    : $ref->getShortName();
            } else {
                $nodeName = 'DTO';
            }
        }

        /** @var truthy-string */
        return $nodeName;
    }
}
