<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\PropAccess\PropAccess;

/**
 * @method static static fromArray(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static fromArrayLoose(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static fromEntity(object $entity, bool $ignoreInaccessibleProps = true, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
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
     * @throws InvalidConfigException
     *
     * @psalm-suppress MethodSignatureMismatch, MoreSpecificReturnType
     */
    public function _fromArray(
        array $input,
        bool $ignoreUnknownProps = false,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,   // <── yes
    ): static {
        // fill the DTO with the input values
        /** @psalm-suppress InaccessibleMethod */
        $toBeFilled = $fillables = $this->getFillable();

        if (!$ignoreUnknownProps) {
            $unknownProperties = array_diff(array_keys($input), $fillables);
            if ($unknownProperties) {
                throw new InvalidConfigException('Unknown properties: '.implode(', ', $unknownProperties));
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
            $inputToBeFilled[$prop] = $mapper($input, $this);
        }

        // and fill the DTO
        $this->fill($inputToBeFilled);

        // cast the values to their respective types and return the DTO
        if ($this instanceof ProcessesInterface) {
            /** @psalm-suppress UnusedMethodCall */
            $this->processInbound($errorList, $errorMode);
        }

        // call post-load hook
        /** @psalm-suppress UndefinedMethod */
        $this->postLoad();

        return $this;
    }

    /**
     * Create a new instance of the DTO from a request, ignoring unknown properties.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress PossiblyUnusedMethod, MethodSignatureMismatch
     */
    public function _fromArrayLoose(
        array $input,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,   // <── yes
    ): static {
        /** @psalm-suppress NoValue */
        return $this->_fromArray($input, ignoreUnknownProps: true, errorList: $errorList, errorMode: $errorMode);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, MethodSignatureMismatch
     */
    public function _fromEntity(
        object $entity,
        bool $ignoreInaccessibleProps = true,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,   // <── yes
    ): static {
        /** @var array */
        $inputData = PropAccess::getValueMap(
            valueSource: $entity,
            propNames: $this->getFillable(),
            ignoreInaccessibleProps: $ignoreInaccessibleProps,
        );

        return $this->_fromArray($inputData, true, $errorList, $errorMode);
    }
}
