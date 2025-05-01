<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Support\CaseConverter;

/**
 * @psalm-require-extends BaseDto
 **/
trait ExportsToEntity
{
    /**
     * The class name of the entity that this DTO maps to.
     * Optional since not all DTOs are mapped to entities.
     *
     * @var class-string
     *
     * @psalm-suppress PossiblyUnusedProperty
     **/
    protected static ?string $entityClass = null;

    /**
     * Convert the DTO to an entity.
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @param object|null $entity  The entity to fill. If null, a new instance will
     *                             be created from static::$entityClass.
     * @param array       $context Additional data to set on the entity. This can be used to set
     *                             relations or other properties that are not part of the DTO.
     *
     * @throws \LogicException
     */
    public function toEntity(?object $entity = null, array $context = []): object
    {
        /** @psalm-suppress RedundantCondition */
        if (!$this instanceof BaseDto) {
            throw new \LogicException('DTO must extend BaseDto to use ExportsToEntity trait.');
        }

        // Get properties already cast, ready to to be set on entity
        /** @psalm-suppress UndefinedMethod */
        $normalizedProps = $this->toOutboundArray(runPreOutputHook: false);

        /** @psalm-suppress InvalidOperand */
        $propsToSet = [...$normalizedProps, ...$context];

        if (!$entity) {
            // If no entity is passed, create a new one
            /** @psalm-suppress UndefinedMagicMethod */
            [$entity, $propsAreLoaded] = $this->newEntityInstance($propsToSet);
            // Could be that a subclass has its own way of preparing the entity, and the data is already loaded
            if ($propsAreLoaded) {
                // If the entity is already set, we don't need to set the properties again
                return $entity;
            }
        }

        /** @psalm-suppress UndefinedMagicMethod */
        $setters = $this->getEntitySetterMap($entity, array_keys($propsToSet));

        foreach ($propsToSet as $prop => $value) {
            $setters[$prop]($value);
        }

        // call pre-output hook
        /** @psalm-suppress UndefinedMethod */
        $this->preOutput($entity);

        /** @var object $entity */
        return $entity;
    }

    /**
     * Create a new instance of the entity class.
     * Can be subclassed.
     *
     * @throws \LogicException
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function newEntityInstance(array $inputData = []): array
    {
        // we assume that $this instanceof BaseDto

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (empty(static::$entityClass)) {
            throw new \LogicException('No entity class specified on DTO '.$this::class.' for auto-instanciation.');
        }

        if (!class_exists(static::$entityClass)) {
            throw new \LogicException('Entity class '.static::$entityClass.' does not exist');
        }

        $entity = new static::$entityClass();

        // TODO: run inject() after instanciation if instance is injectable

        return [$entity, false];
    }

    /**
     * Get a map of closure setters for the given properties.
     *
     * @return \Closure[]
     *
     * @throws \LogicException
     */
    protected function getEntitySetterMap(object $entity, array $propNames): array
    {
        $entityReflection = new \ReflectionClass($entity);
        $entityClass = $entityReflection->getName();

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

                // make a setter name in camelCase from potentially snake_case $prop name
                $setter = 'set'.CaseConverter::toPascal($prop);

                if ($entityReflection->getMethod($setter)->isPublic()) {
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
            throw new \LogicException("No public setter or property found for '{$prop}' in ".$entityClass);
        }

        return $map;
    }
}
