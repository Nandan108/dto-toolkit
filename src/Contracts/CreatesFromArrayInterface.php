<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface CreatesFromArrayInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function _fromArray(array $input, bool $ignoreUnknownProps = false): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function _fromArrayLoose(array $input): static;
}
