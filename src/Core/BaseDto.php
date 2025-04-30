<?php

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Enum\Phase;

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
    private static array $_propertyMetadataCache = [];

    public static function loadPropertyMetadata(Phase $phase, ?string $metaDataName = null): array
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

                foreach ($attributes as $attribute) {
                    $attrInstance = $attribute->newInstance();

                    if ($attrInstance instanceof Outbound) {
                        $isOutbound = true;
                        continue;
                    }

                    if ($attrInstance instanceof PhaseAwareInterface) {
                        $attrInstance->setOutbound($isOutbound);

                        $cache[$attrInstance->getPhase()->value][$propName]['attr'][] = $attrInstance;
                    }
                }
            }

            self::$_propertyMetadataCache[static::class] = $cache;
        }

        /** @var array $meta */
        $meta = self::$_propertyMetadataCache[static::class][$phase->value] ?? [];

        if (null === $metaDataName) {
            return $meta;
        }

        return array_map(
            static function (array $propMeta) use ($metaDataName) {
                return $propMeta[$metaDataName] ?? null;
            },
            $meta,
        );
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
    public function toOutboundArray(bool $runPreOutputHook = true): array
    {
        $propsInScope = $this instanceof ScopedPropertyAccessInterface
            ? array_intersect($this->getPropertiesInScope(Phase::OutboundExport), array_keys($this->_filled))
            : null; // all properties

        $data = $this->toArray($propsInScope);

        if ($this instanceof NormalizesInterface) {
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
    public function unfill(array $props): static
    {
        foreach ($props as $key) {
            unset($this->_filled[$key]);
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
        if (in_array(substr($method, 0, 4), ['from', 'with'])) {
            // if the static method doesn't exist, create a new instance and call the method on it
            /** @psalm-suppress UnsafeInstantiation */
            $instance = new static();
            $realMethodName = "_$method";
            if (method_exists($instance, $realMethodName)) {
                // prepare the instance for use
                if ($instance instanceof Injectable) {
                    /** @psalm-suppress UnusedMethodCall */
                    $instance->inject();
                }
                if ($instance instanceof Bootable) {
                    /** @psalm-suppress UnusedMethodCall */
                    $instance->boot();
                }

                // call the method on the instance
                return $instance->$realMethodName(...$arguments);
            }
        } elseif (method_exists(static::class, $method)) {
            // if the method was public, or visible from context, the call would not have been caught
            // by __callStatic(), therefore this must be a call to a protected or private method.
            throw new \BadMethodCallException('Cannot access non-public static method '.static::class."::{$method}()");
        }

        throw new \BadMethodCallException("Method {$method}() does not exist on ".static::class.'.');
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     *
     * @psalm-mutation-free
     **/
    public function __call(string $method, array $parameters): static
    {
        if (in_array(substr($method, 0, 4), ['from', 'with'])) {
            $forwarded = '_'.$method;
            if (method_exists($this, $forwarded)) {
                return $this->$forwarded(...$parameters);
            }
        }

        throw new \BadMethodCallException("Method {$method}() does not exist on ".static::class.'.');
    }
}
