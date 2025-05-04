<?php

namespace Nandan108\DtoToolkit\Contracts;

interface HasContextInterface
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function setContext(string $key, mixed $value): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function unsetContext(string $key): static;

    public function getContext(string $key, mixed $default = null): mixed;

    public function hasContext(string $key, bool $treatNullAsMissing = true): bool;
}
