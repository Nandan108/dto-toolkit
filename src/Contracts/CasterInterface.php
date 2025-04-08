<?php

namespace Nandan108\SymfonyDtoToolkit\Contracts;

interface CasterInterface
{
    /**
     * Cast the given value.
     *
     * @param mixed $value
     * @param mixed ...$args Optional arguments passed from the CastTo attribute
     * @return mixed
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function cast(mixed $value, mixed ...$args): mixed;
}