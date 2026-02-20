<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/** @api */
interface HasContextInterface
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function contextSet(string $key, mixed $value): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function contextUnset(string $key): static;

    public function contextGet(string $key, mixed $default = null): mixed;

    public function contextHas(string $key, bool $treatNullAsMissing = true): bool;

    /** @return array<non-empty-string, mixed> */
    public function getContext(): array;
}
