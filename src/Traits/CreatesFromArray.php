<?php

namespace Nandan108\SymfonyDtoToolkit\Traits;

use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\SymfonyDtoToolkit\Contracts\ValidatesInputInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

trait CreatesFromArray
{

    /**
     * Create a new instance of the DTO from a request
     *
     * @template T of BaseDto
     * @param array $input
     * @param string|GroupSequence|array|null $groups
     * @return T
     * @throws \LogicException
     */
    public static function fromArray(
        array $input,
        string|GroupSequence|array|null $groups = null,
        BaseDto $dto = null
    ): BaseDto {
        if (!$dto) {
            $dto = new static();

            if (! $dto instanceof BaseDto) {
                throw new \LogicException(static::class . ' must extend BaseDto to use CreatesFromArray.');
            }
        }

        // fill the DTO with the input values
        foreach ($input as $property => $value) {
            $dto->{$property}       = $value;
            $dto->_filled[$property] = true;
        }

        // validate raw input values and throw appropriately in case of violations
        if ($dto instanceof ValidatesInputInterface) {
            $dto->validate($groups);
        } elseif ($groups) {
            throw new \LogicException('To support groups, the DTO must implement ValidatesInput.');
        }

        // cast the values to their respective types and return the DTO
        if ($dto instanceof NormalizesInboundInterface) {
            $dto->normalizeInbound();
        }

        return $dto;
    }
}
