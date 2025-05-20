<?php

namespace Nandan108\DtoToolkit\Support;

final class EntityAccessorHelper
{
    /**
     * Get a map of closure setters for the given properties.
     *
     * @return array<\Closure>
     *
     * @throws \LogicException
     */
    public static function getEntitySetterMap(object $entity, array $propNames, bool $ignoreInaccessibleProps = false): array
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

            if (!$ignoreInaccessibleProps) {
                // No setter or public property found, throw an exception
                throw new \LogicException("No public setter or property found for '{$prop}' in ".$entityClass);
            }
        }

        return $map;
    }

    /**
     * Get the per-property list of getters defined for the given entity.
     * For each property, a closure is created that calls the getter method, or sets
     * the property directly if no getter is found the prop is public.
     *
     * @param bool $ignoreInaccessibleProps If false and no getter is found, an exception is thrown
     *
     * @return array<\Closure> a map of property names to getter closures
     *
     * @throws \LogicException
     */
    public static function getEntityGetterMap(object $entity, array $propNames, bool $ignoreInaccessibleProps = true): array
    {
        $entityReflection = null;
        $entityClass = $entity::class;

        static $getterMap = [];
        $classGetters = $getterMap[$entityClass] ??= [];

        $map = [];
        foreach ($propNames as $prop) {
            if (isset($classGetters[$prop])) {
                $map[$prop] = $classGetters[$prop];
                continue;
            }

            // If we can find a getter method for the property, make a getter closure that uses it
            $entityReflection ??= new \ReflectionClass($entity);
            try {
                // make a setter name in camelCase from potentially snake_case $prop name
                $getter = 'get'.CaseConverter::toPascal($prop);

                // Here we assume that DTO and entity have the same property names
                // and that the entity has a getter for each property
                if ($entityReflection->getMethod($getter)->isPublic()) {
                    $getterMap[$entityClass][$prop] = $map[$prop] =
                        static function (object $entity) use ($getter): mixed {
                            return $entity->$getter();
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
            }

            // No getter found, but the property is public? Make a getter closure that reads directly.
            try {
                if ($entityReflection->getProperty($prop)->isPublic()) {
                    $getterMap[$entityClass][$prop] = $map[$prop] =
                        static function (object $entity) use ($prop): mixed {
                            return $entity->$prop;
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
            }

            if (!$ignoreInaccessibleProps) {
                // No getter or public property found, throw an exception
                throw new \LogicException("No public getter or property found for '{$prop}' in ".$entityClass);
            }
        }

        return $map;
    }
}
