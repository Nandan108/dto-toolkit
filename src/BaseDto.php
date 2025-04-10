<?php

namespace Nandan108\DtoToolkit;

use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;

abstract class BaseDto
{
    /**
     * List of properties that can be filled.
     *
     * @var array[string]
     **/
    protected ?array $_fillable = null;

    /**
     * List of properties that have been filled.
     * The key is the property name, and the value is always true.
     * @var array[true]
     * */
    public array $_filled = [];

    /**
     * The class name of the entity that this DTO maps to.
     * Optional since not all DTOs are mapped to entities.
     *
     * @var class-string
     * @psalm-suppress PossiblyUnusedProperty
     **/
    protected static ?string $entityClass;

    /**
     * Get the names of the public properties of an object
     *
     * @param object|string|null $objectOrClass defaults to the current instance
     * @return array
     */
    protected function getPublicPropNames(object|string $objectOrClass = null): array
    {
        $reflectionClass = new \ReflectionClass($objectOrClass ?? $this);

        $props = [];
        foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isPublic() && $prop->name !== '_filled') {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }

    /**
     * Get the DTO data content as an array.
     * If the DTO implements NormalizesOutboundInterface, the casters (CastTo Attributes)
     * declared with $outbound = true will be called to transform the data before returning it.
     *
     * @return class-string|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function toOutboundArray(): array
    {
        $outbound = $this->toArray();

        if ($this instanceof NormalizesOutboundInterface) {
            return $this->normalizeOutbound($outbound);
        }

        return $outbound;
    }

    /**
     * Get the fillable properties of the DTO
     *
     * @return array
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function getFillable(): array
    {
        return $this->_fillable ??= $this->getPublicPropNames($this);
    }

    /**
     * Get DTO properties corresponding to the given property names as an array.
     * If no property names are given, all public, filled properties will be returned.
     * The data is returned as-is, without any transformation.
     *
     * @param string[] $propNames
     * @return array
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
     * @param array $values
     * @return static
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function fill(array $values): static
    {
        foreach ($values as $key => $value) {
            $this->$key         = $value;
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
     * @param array $props
     * @return static
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function unfill(array $props): static
    {
        foreach ($props as $key) {
            unset($this->_filled[$key]);
        }

        return $this;
    }
}
