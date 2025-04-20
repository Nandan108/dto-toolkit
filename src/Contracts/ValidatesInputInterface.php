<?php

namespace Nandan108\DtoToolkit\Contracts;

interface ValidatesInputInterface
{
    /**
     * Instanciates and calls a validator.
     *
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue
     */
    public function validate(array $args = []): static;
}
