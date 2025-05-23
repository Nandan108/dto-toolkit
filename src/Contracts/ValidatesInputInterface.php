<?php

namespace Nandan108\DtoToolkit\Contracts;

interface ValidatesInputInterface
{
    /**
     * Instanciates and calls a validator.
     * $args maybe used to pass additional arguments to the validator.
     * However, it would be better to use the context to pass additional arguments.
     *
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue
     */
    public function validate(): static;
}
