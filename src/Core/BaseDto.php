<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\Presence;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;

/**
 * @method static static newWithErrorMode(ErrorMode $mode)
 *
 * @psalm-type EntityClassGroupMap = array<list<string>>
 * @psalm-type DtoPropMetaCache = array{
 *     defaultValue: array<string, mixed>,
 *     propRef: array<string, \ReflectionProperty>,
 *     defaultOutboundEntityClasses: EntityClassGroupMap|null, // map of entity class to scoping groups
 *     presencePolicy:  array<string, PresencePolicy>,
 *     classRef?: \ReflectionClass<static>,
 *     phase?: array<string, array<string, PhaseAwareInterface|list<PhaseAwareInterface>>>,
 * }
 */
abstract class BaseDto
{
    protected static ErrorMode $defaultErrorMode = ErrorMode::FailFast;
    protected ?ErrorMode $errorMode = null;
    protected ?ProcessingErrorList $errorList = null;

    /**
     * List of properties that can be filled.
     *
     * @var string[]
     **/
    protected ?array $_fillable = null;
    /**
     * List of properties that have been filled.
     * The key is the property name, and the value is ALWAYS true.
     * An unfilled property is simply not present in this array.
     *
     * @var array<string, true>
     **/
    public array $_filled = [];

    /**
     * Full metadata cache per class (static).
     *
     * @psalm-import-type DtoPropMetaCache from self
     *
     * @psalm-var array<class-string, DtoPropMetaCache>
     */
    private static array $_propertyMetadataCache = [];

    public static function setDefaultErrorMode(ErrorMode $mode): void
    {
        static::$defaultErrorMode = $mode;
    }

    public function getErrorMode(): ErrorMode
    {
        return $this->errorMode ?? static::$defaultErrorMode;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function withErrorMode(ErrorMode $mode): static
    {
        $this->errorMode = $mode;

        return $this;
    }

    public function setErrorList(ProcessingErrorList $newErrorList): ProcessingErrorList
    {
        return $this->errorList = $newErrorList;
    }

    public function getErrorList(): ProcessingErrorList
    {
        return $this->errorList ??= new ProcessingErrorList();
    }

    /**
     * Initialize phase-agnostic property metadata for a DTO class.
     * For each prop, gathers :
     * - ReflectionProperty
     * - default value
     * - presence policy.
     *
     * @param class-string $class
     */
    private static function initPropMeta(string $class): void
    {
        self::$_propertyMetadataCache[$class] ??= [
            'defaultValue'                 => [],
            'propRef'                      => [],
            'defaultOutboundEntityClasses' => null,
        ];
        $refClass = static::getClassRef();

        foreach ($refClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $propName = $prop->getName();

            // Ignore static properties
            if ($prop->isStatic() || '_' === $propName[0]) {
                continue;
            }

            self::$_propertyMetadataCache[$class]['propRef'][$propName] = $prop;

            if ($prop->hasDefaultValue()) {
                self::$_propertyMetadataCache[$class]['defaultValue'][$propName] = $prop->getDefaultValue();
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

        foreach (self::$_propertyMetadataCache[$class]['propRef'] as $propName => $propRef) {
            $policy = $dtoDefaultPolicy;

            // Property-level override
            if ($attr = $propRef->getAttributes(Presence::class)[0] ?? null) {
                $policy = $attr->newInstance()->policy;
            }

            $resolved[$propName] = $policy;
        }

        self::$_propertyMetadataCache[$class]['presencePolicy'] = $resolved;
    }

    /**
     * @psalm-return DtoPropMetaCache
     */
    protected static function getPropMeta(): array
    {
        $class = static::class;

        if (!isset(self::$_propertyMetadataCache[$class]['propRef'])) {
            self::initPropMeta($class);
        }

        return self::$_propertyMetadataCache[$class];
    }

    /** @return array<string, \ReflectionProperty> */
    protected static function getPropRefs(): array
    {
        return static::getPropMeta()['propRef'];
    }

    /** @return array<string, mixed> */
    public static function getDefaultValues(): array
    {
        return static::getPropMeta()['defaultValue'];
    }

    /** @return array<string, PresencePolicy> */
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

                /** @psalm-suppress ArgumentTypeCoercion */
                /** @var int $flags */
                $flags = (new \ReflectionClass($attributeRef->getName()))->getAttributes()[0]->newInstance()->flags;
                $isRepeatable = ($flags & \Attribute::IS_REPEATABLE) === \Attribute::IS_REPEATABLE;

                if ($attrInstance instanceof Outbound) {
                    $isOutbound = true;
                    continue;
                }

                if ($attrInstance instanceof PhaseAwareInterface) {
                    $attrInstance->setOutbound($isOutbound);
                    if ($isRepeatable) {
                        /** @var array<array<array{attr?: list<PhaseAwareInterface>}>> $cache */
                        $cache[$attrInstance->getPhase()->value][$propName]['attr'][] = $attrInstance;
                    } else {
                        $cache[$attrInstance->getPhase()->value][$propName]['attr'] = $attrInstance;
                    }
                }
            }
        }

        /** @psalm-var array<string, array<string, PhaseAwareInterface|list<PhaseAwareInterface>>> $cache */
        self::$_propertyMetadataCache[static::class]['phase'] = $cache;
    }

    /**
     * Load the metadata for the given phase.
     *
     * @param \Closure|class-string|null $filter
     *
     * @return array<string, PhaseAwareInterface|list<PhaseAwareInterface>>
     */
    public static function getPhaseAwarePropMeta(Phase $phase, string $metaDataName, \Closure | string | null $filter, bool $ignoreEmpty = false): array
    {
        if (!isset(self::$_propertyMetadataCache[static::class]['phase'])) {
            self::initPhaseAwarePropMeta();
        }

        /** @var array $meta */
        $meta = self::$_propertyMetadataCache[static::class]['phase'][$phase->value] ?? [];

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
                /** @psalm-suppress TooManyArguments */
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

        /** @psalm-suppress InvalidReturnStatement */
        return $data;
    }

    /**
     * Get the fillable properties of the DTO.
     * These are public instance properties, excluding those prefixed with "_".
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getFillable(): array
    {
        // Get the names of static's public properties, excluding those prefixed with "_".
        return $this->_fillable ??= array_keys(static::getPropRefs());
    }

    /**
     * Check if the given property has been filled.
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
     * @param string[] $propNames
     */
    public function toArray(?array $propNames = null): array
    {
        $vars = get_object_vars($this);
        $keys = $propNames ?? array_keys($this->_filled);

        return array_intersect_key($vars, array_flip($keys));
    }

    /**
     * Reset public properties (all or specified) to their default values.
     * Properties prefixed with "_" are considered internal and are not cleared.
     *
     * This method is useful to prepare the DTO for safe reuse.
     *
     * @param list<string>         $propNames        specific property names to clear. If empty, all public props are cleared.
     * @param array<string, mixed> $excludedPropsMap a property-name keyed map. These props will NOT be cleared.
     * @param bool                 $clearErrors      whether to clear DTO's errorList
     *
     * @psalm-suppress PossiblyUnusedMethod
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
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function fill(array $values): static
    {
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
     * @psalm-suppress PossiblyUnusedMethod
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
     */
    public function preOutput(array | object &$outputData)
    {
        // no-op - to be implemented in subclasses
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     **/
    public static function __callStatic(string $method, array $arguments): static
    {
        $ref = static::getClassRef();
        static $resolvedMethods = [];
        /** @psalm-suppress  UnsupportedReferenceUsage */
        $resolvedInstanceMethod = &$resolvedMethods[$ref->name.'::'.$method] ?? null;

        if (!$resolvedInstanceMethod) {
            if (str_starts_with($method, $prefix = 'newFrom')
                || str_starts_with($method, $prefix = 'newWith')) {
                $instanceMethodName = match ($prefix) {
                    'newFrom' => 'load',
                    'newWith' => 'with',
                }.substr($method, strlen($prefix));
                $resolvedInstanceMethod = self::getValidMethodRef($instanceMethodName, $ref);

            } else {
                // if __callStatic was called, it means the method doesn't exist, therefore getValidMethodRef() will throw.
                self::getValidMethodRef($method, $ref, false);
            }
        }

        // make a new instance of the class
        $instance = static::new();
        // call the method on the instance
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

    protected static function getClassRef(): \ReflectionClass
    {
        /** @psalm-suppress PropertyTypeCoercion */
        return static::$_propertyMetadataCache[static::class]['classRef'] ??=
            new \ReflectionClass(static::class);
    }

    protected static function getValidMethodRef(string $method, \ReflectionClass $classRef, bool $visible = true): \ReflectionMethod
    {
        $className = $classRef->getName();
        if (!$classRef->hasMethod($method)) {
            throw new InvalidConfigException("Method $className::{$method}() does not exist.");
        }
        $methodRef = $classRef->getMethod($method);
        $visibility = $methodRef->isPublic() ? 'public' : ($methodRef->isProtected() ? 'protected' : 'private');
        if (!$visible || $methodRef->isPrivate()) {
            throw new InvalidConfigException(ucfirst($visibility)." method $className::{$method}() is not reachable from calling context.");
        }

        return $methodRef;
    }

    /**
     * Get the default outbound entity class for this DTO, based on the DefaultOutboundEntity attributes.
     *
     * @return ?class-string
     *
     * @throws InvalidConfigException
     */
    public function getDefaultOutboundEntityClass(): ?string
    {
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $meta = &self::$_propertyMetadataCache[static::class]['defaultOutboundEntityClasses'];
        $meta ??= [];

        // initialize defaultOutboundEntityClasses cache if not done yet
        if (!\array_key_exists('defaultOutboundEntityClasses', $meta)) {
            // initialize values from DefaultOutboundEntity attribute
            $refClass = static::getClassRef();
            $attrs = $refClass->getAttributes(DefaultOutboundEntity::class);
            $implementsGroups = $this instanceof HasGroupsInterface;
            foreach ($attrs as $attrRef) {
                $attrInstance = $attrRef->newInstance();
                if ($attrInstance->groups && !$implementsGroups) {
                    throw new InvalidConfigException('The DefaultOutboundEntity attribute on DTO '.static::class.' declares scoping groups, but the DTO does not implement HasGroupsInterface.');
                }
                // throw if entity class does not exist
                if (!class_exists($attrInstance->entityClass)) {
                    throw new InvalidConfigException("Entity class '$attrInstance->entityClass' does not exist");
                }
                $meta[$attrInstance->entityClass] = (array) $attrInstance->groups;
            }
        }

        // filter by scoping groups if any -- Phase: OutboundExport
        foreach ($meta as $entityClass => $groups) {
            if (empty($groups)
                || ($this instanceof HasGroupsInterface
                    && $this->groupsAreInScope(Phase::OutboundExport, $groups))) {
                return $entityClass;
            }
        }

        return null;
    }
}
