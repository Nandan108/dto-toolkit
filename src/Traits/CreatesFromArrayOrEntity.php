<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Support\EntityAccessorHelper;

/**
 * @method static static fromArray(array $input, bool $ignoreUnknownProps = false)
 * @method static static fromArrayLoose(array $input, bool $ignoreUnknownProps = false)
 * @method static static fromEntity(object $entity, bool $ignoreInaccessibleProps = true)
 *
 * These methods are dynamically routed via __call() and __callStatic() to their corresponding instance methods.
 * Static analyzers require the above annotations to avoid false positives.
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

        // get mappers for the properties to be filled
        $mappers = MapFrom::getMappers($this, $toBeFilled);

        $inputToBeFilled = array_intersect_key($input, array_flip($toBeFilled));

        // Get data from mappers. Only fill if we actually get a value.
        foreach ($mappers as $prop => $mapper) {
            $value = $mapper($input, $this) ?? $inputToBeFilled[$prop] ?? null;
            if (null !== $value) {
                $inputToBeFilled[$prop] = $value;
            }
        }

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
     * @psalm-suppress PossiblyUnusedMethod, MethodSignatureMismatch
     */
    public function _fromArrayLoose(array $input): static
    {
        /** @psalm-suppress NoValue */
        return $this->_fromArray($input, ignoreUnknownProps: true);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, MethodSignatureMismatch
     */
    public function _fromEntity(object $entity, bool $ignoreInaccessibleProps = true): static
    {
        $inputData = [];

        $getterMap = EntityAccessorHelper::getEntityGetterMap(
            entity: $entity,
            propNames: $this->getFillable(),
            ignoreInaccessibleProps: $ignoreInaccessibleProps
        );
        foreach ($getterMap as $prop => $getFrom) {
            $inputData[$prop] = $getFrom($entity);
        }

        return $this->_fromArray($inputData, true);
    }
}
