<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface CasterInterface
{
    /**
     * Cast the given value.
     *
     * @param array $args Optional arguments passed from the CastTo attribute
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function cast(mixed $value, array $args): mixed;
}
