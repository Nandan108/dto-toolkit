<?php

namespace Nandan108\DtoToolkit\Contracts;

interface ValidatesInputInterface
{
    /**
     * Instanciates and calls a validator
     *
     * @return void
     */
    public function validate(array $args = []): static;
}

