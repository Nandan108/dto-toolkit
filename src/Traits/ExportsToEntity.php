<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\BaseDto;

trait ExportsToEntity
{
    /**
     * Convert the DTO to an entity
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @throws \LogicException
     * @return object
     */
    public function toEntity($entity = null, array $context = []): object
    {
        $entity ??= $this->newEntityInstance();

        // Get properties already type-cast, ready to to be set on entity
        $props = [...$this->toOutboundArray(), ...$context];

        $setters = $this->getEntitySetterMap(array_keys($props), $entity);

        // Merge in context props (relations, injected domain values)
        foreach ($props as $prop => $value) {
            $method = $setters[$prop];
            $method($value);
        }

        return $entity;
    }

    /**
     * Create a new instance of the entity class
     *
     * @throws \LogicException
     * @return object
     */
    protected function newEntityInstance(): object
    {
        if (!$this instanceof BaseDto) {
            throw new \LogicException('DTO must extend BaseDto to use ExportsToEntity.');
        }

        if (empty(static::$entityClass)) {
            throw new \LogicException('No entity class defined for DTO ' . get_class($this));
        }

        if (!class_exists(static::$entityClass)) {
            throw new \LogicException('Entity class ' . static::$entityClass . ' does not exist');
        }

        return new static::$entityClass();
    }

    /**
     * Get a map of closure setters for the given properties
     *
     * @param null|array $propNames
     * @param object $entity
     * @return \Closure[]
     * @throws \LogicException
     */
    protected function getEntitySetterMap(?array $propNames, object $entity): array
    {
        $entityReflection = new \ReflectionClass($entity);
        $entityClass     = $entityReflection->getName();

        static $setterMap = [];
        $classSetters = $setterMap[$entityClass] ??= [];

        $map = [];
        foreach ($propNames as $prop) {
            if (isset($classSetters[$prop])) {
                $map[$prop] = $classSetters[$prop];
                continue;
            }

            // If we can find a setter method for the property, make a setter closure that uses it
            try {
                // Here we assume that DTO and entity have the same property names
                // and that the entity has a setter for each property
                if ($entityReflection->getMethod($setter = 'set' . ucfirst($prop))->isPublic()) {
                    $setterMap[$entityClass][$prop] = $map[$prop] =
                        static function (mixed $value) use ($entity, $setter) {
                            $entity->$setter($value);
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {}

            // No setter found, but the property is public? Make a setter closure that assigns directly.
            try {
                if ($entityReflection->getProperty($prop)->isPublic()) {
                    $setterMap[$entityClass][$prop] = $map[$prop] =
                        static function (mixed $value) use ($entity, $prop) {
                            $entity->$prop = $value;
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {}

            // No setter or public property found, throw an exception
            throw new \LogicException("No public setter or property found for '{$prop}' in " . $entityClass);
        }

        return $map;
    }
}