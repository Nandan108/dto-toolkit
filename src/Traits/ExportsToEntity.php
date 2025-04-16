<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Core\BaseDto;

/**
 * @psalm-require-extends BaseDto
 **/
trait ExportsToEntity
{
    /**
     * Convert the DTO to an entity
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @param object|null $entity The entity to fill. If null, a new instance will
     *  be created from static::$entityClass.
     * @param array $context Additional data to set on the entity. This can be used to set
     *  relations or other properties that are not part of the DTO.
     * @throws \LogicException
     * @return object
     */
    public function toEntity(object $entity = null, array $context = []): object
    {
        $entity ??= $this->newEntityInstance();

        // Get properties already cast, ready to to be set on entity
        /** @psalm-suppress UndefinedMethod */
        $normalizedProps = $this->toOutboundArray();

        /** @psalm-suppress InvalidOperand */
        $propsToSet = [...$normalizedProps, ...$context];
        $setters = $this->getEntitySetterMap($entity, array_keys($propsToSet));

        foreach ($propsToSet as $prop => $value) {
            $setters[$prop]($value);
        }

        // call pre-output hook
        /** @psalm-suppress UndefinedMethod */
        $this->preOutput($entity);

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
        /** @psalm-suppress RedundantCondition */
        if (!$this instanceof BaseDto) {
            throw new \LogicException('DTO must extend BaseDto to use ExportsToEntity.');
        }

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (empty(static::$entityClass)) {
            throw new \LogicException('No entity class defined for DTO ' . get_class($this));
        }

        if (!class_exists(static::$entityClass)) {
            throw new \LogicException('Entity class ' . static::$entityClass . ' does not exist');
        }

        return new static::$entityClass();
    }

    /**
     * Get a map of closure setters for the given properties.
     *
     * @param object $entity
     * @param array $propNames
     * @return \Closure[]
     * @throws \LogicException
     */
    protected function getEntitySetterMap(object $entity, array $propNames): array
    {
        $entityReflection = new \ReflectionClass($entity);
        $entityClass      = $entityReflection->getName();

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
                        static function (mixed $value) use ($entity, $setter): void {
                            $entity->$setter($value);
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
            }

            // No setter found, but the property is public? Make a setter closure that assigns directly.
            try {
                if ($entityReflection->getProperty($prop)->isPublic()) {
                    $setterMap[$entityClass][$prop] = $map[$prop] =
                        static function (mixed $value) use ($entity, $prop): void {
                            $entity->$prop = $value;
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
            }

            // No setter or public property found, throw an exception
            throw new \LogicException("No public setter or property found for '{$prop}' in " . $entityClass);
        }

        return $map;
    }
}