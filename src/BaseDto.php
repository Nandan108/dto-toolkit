<?php

namespace Nandan108\SymfonyDtoToolkit;

use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesOutboundInterface;

abstract class BaseDto
{
    /**
     * List of sources to get the input from.
     * Values possible: COOKIE, POST, PARAMS, GET
     * All sources will be visited in the order they're given, and merged with the result.
     * This means that if a value is present in multiple sources, the last one will be used.
     *
     * @var array
     */
    protected array $_inputSources = ['POST'];

    /**
     * List of properties that can be filled.
     *
     * @var array[string]
     **/
    protected ?array $fillable = null;

    /**
     * List of properties that have been filled.
     * The key is the property name, and the value is always true.
     * @var array[true]
     * */
    public array $filled = [];

    /**
     * The class name of the entity that this DTO maps to.
     * Optional since not all DTOs are mapped to entities.
     *
     * @var class-string
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
            if ($prop->isPublic()) {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }



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
     */
    protected function getFillable(): array
    {
        return $this->fillable ??= $this->getPublicPropNames($this);
    }

    /**
     * Return an array of DTO properties corresponding to the given property names.
     * If no property names are given, all public, filled properties will be returned.
     *
     * @param string[] $propNames
     * @return array
     */
    public function toArray(?array $propNames = null): array
    {
        $vars = get_object_vars($this);
        $keys = $propNames ?? array_keys($this->filled);

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
     */
    public function fill(array $values): static
    {
        foreach ($values as $key => $value) {
            $this->$key         = $value;
            $this->filled[$key] = true;
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
            unset($this->filled[$key]);
        }

        return $this;
    }
}
