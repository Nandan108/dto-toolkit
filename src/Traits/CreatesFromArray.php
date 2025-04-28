<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
// use Nandan108\DtoToolkit\Contracts\CreatesFromArrayInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;

/**
 * @method static static fromArray(array $input, bool $ignoreUnknownProps = false)
 * @method static static fromArrayLoose(array $input, bool $ignoreUnknownProps = false)
 *
 * These methods are dynamically routed via __call() and __callStatic() to their corresponding instance methods.
 * Static analyzers require the above annotations to avoid false positives.
 *
 * @psalm-require-extends BaseDto
 *
 **/
trait CreatesFromArray
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

        if ($this instanceof ScopedPropertyAccessInterface) {
            $propsInScope = $this->getPropertiesInScope(Phase::InboundLoad);
            $toBeFilled = array_intersect($fillables, $propsInScope);
        }

        $inputToBeFilled = array_intersect_key($input, array_flip($toBeFilled));
        $this->fill($inputToBeFilled);

        // validate raw input values
        if ($this instanceof ValidatesInputInterface) {
            // args not passed directly, let validator pull from groups or context
            /** @psalm-suppress UnusedMethodCall */
            $this->validate();
        }

        // cast the values to their respective types and return the DTO
        if ($this instanceof NormalizesInboundInterface) {
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
     * @psalm-suppress MethodSignatureMismatch
     */
    public function _fromArrayLoose(array $input): static
    {
        /** @psalm-suppress NoValue */
        return $this->_fromArray($input, ignoreUnknownProps: true);
    }
}
