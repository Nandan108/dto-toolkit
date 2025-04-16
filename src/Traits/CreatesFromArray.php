<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;

/**
 * @psalm-require-extends BaseDto
 **/
trait CreatesFromArray
{

    /**
     * Create a new instance of the DTO from a request
     *
     * @param array $input
     * @param array $validationArgs Array of arguments to pass to the validator
     * @param BaseDto|null $dto The DTO to use. If null, a new instance will be created.
     * @param bool $ignoreUnknownProps If true, unknown properties will be ignored
     * @throws \LogicException
     */
    public static function fromArray(
        array $input,
        array $validationArgs = [],
        ?BaseDto $dto = null,
        bool $ignoreUnknownProps = false,
    ): BaseDto {
        if (!$dto) {
            /** @psalm-suppress UnsafeInstantiation */
            $dto = new static();

            /** @psalm-suppress TypeDoesNotContainType, RedundantCondition */
            if (!$dto instanceof BaseDto) {
                throw new \LogicException(static::class . ' must extend BaseDto to use CreatesFromArray.');
            }
        }

        // fill the DTO with the input values
        /** @psalm-suppress InaccessibleMethod */
        $fillables     = $dto->getFillable();
        $fillableInput = array_intersect_key($input, array_flip($fillables));
        foreach ($fillableInput as $property => $value) {
            $dto->{$property}        = $value;
            $dto->_filled[$property] = true;
        }
        if (!$ignoreUnknownProps) {
            $unknownProperties = array_diff(array_keys($input), $fillables);
            if ($unknownProperties) {
                throw new \LogicException('Unknown properties: ' . implode(', ', $unknownProperties));
            }
        }

        // validate raw input values and throw appropriately in case of violations
        if ($dto instanceof ValidatesInputInterface) {
            $dto->validate($validationArgs);
        } elseif ($validationArgs) {
            throw new \LogicException('To support $args, the DTO must implement ValidatesInput.');
        }

        // cast the values to their respective types and return the DTO
        if ($dto instanceof NormalizesInboundInterface) {
            $dto->normalizeInbound();
        }

        // call post-load hook
        $dto->postLoad();

        return $dto;
    }

    /**
     * Create a new instance of the DTO from a request, ignoring unknown properties
     *
     * @param array $input
     * @param array $validationArgs Array of arguments to pass to the validator
     * @param BaseDto|null $dto The DTO to use. If null, a new instance will be created.
     * @return BaseDto
     *
     * @throws \LogicException
     */
    public static function fromArrayLoose(array $input, array $validationArgs = [], ?BaseDto $dto = null): BaseDto
    {
        /** @psalm-suppress NoValue */
        return static::fromArray($input, $validationArgs, $dto, ignoreUnknownProps: true);
    }
}
