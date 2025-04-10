<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;

trait CreatesFromArray
{

    /**
     * Create a new instance of the DTO from a request
     *
     * @template T of BaseDto
     * @param array $input
     * @param array $args
     * @return T
     * @throws \LogicException
     */
    public static function fromArray(
        array $input,
        array $args = [],
        BaseDto $dto = null,
        bool $ignoreUnknownProperties = true,
    ): BaseDto {
        if (!$dto) {
            $dto = new static();

            if (! $dto instanceof BaseDto) {
                throw new \LogicException(static::class . ' must extend BaseDto to use CreatesFromArray.');
            }
        }

        // fill the DTO with the input values
        $fillables = $dto->getFillable();
        $fillableInput = array_intersect_key($input, array_flip($fillables));
        foreach ($fillableInput as $property => $value) {
            $dto->{$property}       = $value;
            $dto->_filled[$property] = true;
        }
        if (!$ignoreUnknownProperties) {
            $unknownProperties = array_diff(array_keys($input), $fillables);
            if ($unknownProperties) {
                throw new \LogicException('Unknown properties: ' . implode(', ', $unknownProperties));
            }
        }

        // validate raw input values and throw appropriately in case of violations
        if ($dto instanceof ValidatesInputInterface) {
            $dto->validate($args);
        } elseif ($args) {
            throw new \LogicException('To support $args, the DTO must implement ValidatesInput.');
        }

        // cast the values to their respective types and return the DTO
        if ($dto instanceof NormalizesInboundInterface) {
            $dto->normalizeInbound();
        }

        return $dto;
    }
}
