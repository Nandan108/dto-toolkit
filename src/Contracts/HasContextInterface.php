<?php

namespace Nandan108\DtoToolkit\Contracts;

interface HasContextInterface
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function setContext(string $key, mixed $value): static;

    public function getContext(string $key, mixed $default = null): mixed;
}
