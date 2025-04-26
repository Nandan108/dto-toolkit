<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

interface CasterInterface
{
    /**
     * Cast the given value.
     *
     * @param array $args Optional arguments passed from the CastTo attribute
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function cast(mixed $value, array $args, BaseDto $dto): mixed;
}
