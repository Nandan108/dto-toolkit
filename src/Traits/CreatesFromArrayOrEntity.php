<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
// use Nandan108\DtoToolkit\Contracts\CreatesFromArrayInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Support\CaseConverter;

/**
 * @method static static fromArray(array $input, bool $ignoreUnknownProps = false)
 * @method static static fromArrayLoose(array $input, bool $ignoreUnknownProps = false)
 * @method static static fromEntity(object $entity, bool $ignoreInaccessibleProps = true)
 *
 * These methods are dynamically routed via __call() and __callStatic() to their corresponding instance methods.
 * Static analyzers require the above annotations to avoid false positives.
 *
 * @psalm-require-extends BaseDto
 *
 **/
trait CreatesFromArrayOrEntity
{
    /**
     * Create a new instance of the DTO from a request.
     *
     * @param bool $ignoreUnknownProps If true, unknown properties will be ignored
     *
     * @throws \LogicException
     *
     * @psalm-suppress MethodSignatureMismatch, MoreSpecificReturnType
     */
    public function _fromArray(
        array $input,
        bool $ignoreUnknownProps = false,
    ): static {
        // fill the DTO with the input values
        /** @psalm-suppress InaccessibleMethod */
        $toBeFilled = $fillables = $this->getFillable();

        if (!$ignoreUnknownProps) {
            $unknownProperties = array_diff(array_keys($input), $fillables);
            if ($unknownProperties) {
                throw new \LogicException('Unknown properties: '.implode(', ', $unknownProperties));
            }
        }

        // restrict properties to current scope
        if ($this instanceof ScopedPropertyAccessInterface) {
            $propsInScope = $this->getPropertiesInScope(Phase::InboundLoad);
            $toBeFilled = array_intersect($fillables, $propsInScope);
        }

        // get actual data to be filled
        $inputToBeFilled = array_intersect_key($input, array_flip($toBeFilled));
        // and fill the DTO
        $this->fill($inputToBeFilled);

        // validate raw input values
        if ($this instanceof ValidatesInputInterface) {
            // args not passed directly, let validator pull from groups or context
            /** @psalm-suppress UnusedMethodCall */
            $this->validate();
        }

        // cast the values to their respective types and return the DTO
        if ($this instanceof NormalizesInterface) {
            /** @psalm-suppress UnusedMethodCall */
            $this->normalizeInbound();
        }

        // call post-load hook
        /** @psalm-suppress UndefinedMethod */
        $this->postLoad();

        return $this;
    }

    /**
     * Create a new instance of the DTO from a request, ignoring unknown properties.
     *
     * @throws \LogicException
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function _fromArrayLoose(array $input): static
    {
        /** @psalm-suppress NoValue */
        return $this->_fromArray($input, ignoreUnknownProps: true);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function _fromEntity(object $entity, bool $ignoreInaccessibleProps = true): static
    {
        $inputData = [];

        foreach ($this->getEntityGetters($entity, $this->getFillable(), $ignoreInaccessibleProps) as $prop => $getFrom) {
            $inputData[$prop] = $getFrom($entity);
        }

        return $this->_fromArray($inputData, true);
    }

    /**
     * Get the per-property list of getters defined for the given entity.
     * For each property, a closure is created that calls the getter method, or sets
     * the property directly if no getter is found the prop is public.
     *
     * @param bool $ignoreInaccessibleProps If false and no getter is found, an exception is thrown
     *
     * @throws \LogicException
     */
    protected function getEntityGetters(object $entity, array $propNames, bool $ignoreInaccessibleProps = true): array
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
