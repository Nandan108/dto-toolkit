<?php

namespace Nandan108\DtoToolkit\Contracts;

interface CasterInterface
{
    /**
     * Cast the given value.
     *
     * @param mixed $value
     * @param array $args Optional arguments passed from the CastTo attribute
     * @return mixed
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function cast(mixed $value, array $args = []): mixed;
}