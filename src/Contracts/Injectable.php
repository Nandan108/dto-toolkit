<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface Injectable
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function inject(): static;
}
