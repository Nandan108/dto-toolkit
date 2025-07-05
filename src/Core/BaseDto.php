<?php

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Support\ContainerBridge;

abstract class BaseDto
{
    /**
     * List of properties that can be filled.
     *
     * @var string[]
     **/
    protected ?array $_fillable = null;

    /**
     * List of properties that have been filled.
     * The key is the property name, and the value is always true.
     *
     * @var true[]
     * */
    public array $_filled = [];

    // full metadata cache per class (static)
    /** @var array<array<array-key, array<array-key, array{attr?: PhaseAwareInterface|list<PhaseAwareInterface>}>>> */
    private static array $_propertyMetadataCache = [];

    /**
     * Load the metadata for the given phase.
     *
     * @param \Closure|class-string|null $filter
     *
     * @/return array<string, array<string, mixed>>|array<string, mixed>
     *
     * @return array<array-key, array<never, never>|mixed>
     */
    public static function loadPhaseAwarePropMeta(Phase $phase, string $metaDataName, \Closure|string|null $filter, bool $ignoreEmpty = false): array
    {
        if (!isset(self::$_propertyMetadataCache[static::class])) {
            $cache = [];
            $reflection = new \ReflectionClass(static::class);
            $props = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($props as $prop) {
                $isOutbound = false;
                $propName = $prop->getName();
                if ('_' === $propName[0]) {
                    continue; // skip internal properties
                }
                $attributes = $prop->getAttributes();

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

            self::$_propertyMetadataCache[static::class] = $cache;
        }

        /** @var array $meta */
        $meta = self::$_propertyMetadataCache[static::class][$phase->value] ?? [];

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
     * Get the names of the public properties of an object.
     *
     * @param object|class-string|null $objectOrClass defaults to the current instance
     */
    protected function getPublicPropNames(object|string|null $objectOrClass = null, ?\ReflectionClass $reflectionClass = null): array
    {
        $reflectionClass ??= new \ReflectionClass($objectOrClass ?? $this);

        $props = [];
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isPublic() && '_filled' !== $prop->name) {
                $props[] = $prop->getName();
            }
        }

        return $props;
    }

    /**
     * Get the DTO data content as an array.
     * If the DTO implements NormalizesOutboundInterface, the casters (CastTo Attributes)
     * declared after #[Outbound] will be called to transform the data before returning it.
     *
     * @psalm-suppress PossiblyUnusedMethod, UnusedParam, InvalidReturnType
     */
    public function toOutboundArray(bool $runPreOutputHook = true, bool $applyOutboundMappings = true): array
    {
        if ($this instanceof ScopedPropertyAccessInterface) {
            // If the DTO implements ScopedPropertyAccessInterface, we only export properties in scope.
            // This is useful for DTOs that have different scopes for different phases.
            /** @var string[] */
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

        if ($this instanceof NormalizesInterface) {
            /** @var array<array-key, mixed> */
            $data = $this->normalizeOutbound($data);
        }

        if ($runPreOutputHook) {
            $this->preOutput($data);
        }

        /** @psalm-suppress InvalidReturnStatement */
        return $data;
    }

    /**
     * Get the fillable properties of the DTO.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getFillable(): array
    {
        return $this->_fillable ??= $this->getPublicPropNames($this);
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
     * Fill the DTO with the given values.
     *
     * This method will set the value of given properties and mark them as filled.
     * This means that they will be included in further processing such as
     * normalization, export, or entity mapping.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function fill(array $values): static
    {
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $this->$key = $value;
                $this->_filled[$key] = true;
            }
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
    public function preOutput(array|object &$outputData)
    {
        // no-op - to be implemented in subclasses
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     **/
    public static function __callStatic(string $method, array $arguments): static
    {
        $ref = static::getClassRef();

        if (str_starts_with($method, 'from') || str_starts_with($method, 'with')) {
            $methodRef = self::getValidMethodRef("_$method", $ref);

            $instance = static::newInstance();

            // call the method on the instance
            $instance = $methodRef->invoke($instance, ...$arguments);
        } else {
            // if __callStatic was called, it means the method doesn't exist, therefore getValidMethodRef() will throw.
            self::getValidMethodRef($method, $ref, false);
        }

        // $instance is always set in the magic method case, but adding `?? new static()`
        // keeps Psalm and IDEs happy (ensures return type is always `static`) and avoids
        // unreachable throw lines that would break test coverage goals.
        /** @psalm-suppress UnsafeInstantiation */
        return $instance ?? new static();
    }

    /**
     * Create a new instance of the DTO.
     * This method will use the container to create the instance if the class is marked with #[Inject].
     * If the class is not marked with #[Inject], it will create a new instance using `new static()`.
     *
     * This method will also call the `inject()` method if the class implements Injectable,
     * and the `boot()` method if the class implements Bootable.
     */
    public static function newInstance(): static
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
            /** @psalm-suppress UnusedMethodCall */
            $instance->inject();
        }
        if ($instance instanceof Bootable) {
            /** @psalm-suppress UnusedMethodCall */
            $instance->boot();
        }

        $defaultGroupsRef = $classRef->getAttributes(WithDefaultGroups::class)[0] ?? null;
        if ($defaultGroupsRef) {
            $defaultGroupsRef->newInstance()->applyToDto($instance);
        }

        /** @var static $instance */
        return $instance;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     **/
    public function __call(string $method, array $parameters): static
    {
        $ref = static::getClassRef();

        if (str_starts_with($method, 'from') || str_starts_with($method, 'with')) {
            $methodRef = self::getValidMethodRef("_$method", $ref);
            $methodRef->invoke($this, ...$parameters);
        } else {
            self::getValidMethodRef($method, $ref, false);
        }

        return $this;
    }

    protected static function getClassRef(): \ReflectionClass
    {
        static $refs = [];
        $class = static::class;

        return $refs[$class] ??= new \ReflectionClass($class);
    }

    protected static function getValidMethodRef(string $method, \ReflectionClass $classRef, bool $visible = true): \ReflectionMethod
    {
        $className = $classRef->getName();
        if (!$classRef->hasMethod($method)) {
            throw new \BadMethodCallException("Method $className::{$method}() does not exist.");
        }
        $methodRef = $classRef->getMethod($method);
        $visibility = $methodRef->isPublic() ? 'public' : ($methodRef->isProtected() ? 'protected' : 'private');
        if (!$visible || $methodRef->isPrivate()) {
            throw new \BadMethodCallException(ucfirst($visibility)." method $className::{$method}() is not reachable from calling context.");
        }

        return $methodRef;
    }
}
