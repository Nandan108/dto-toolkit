<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface CreatesFromArrayInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function loadArray(array $input, bool $ignoreUnknownProps = false): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function loadArrayLoose(array $input): static;
}
