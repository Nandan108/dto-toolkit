<?php

namespace Nandan108\DtoToolkit\Contracts;

interface Injectable
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function inject(): static;
}
