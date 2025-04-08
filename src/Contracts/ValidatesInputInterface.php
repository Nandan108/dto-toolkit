<?php

namespace Nandan108\SymfonyDtoToolkit\Contracts;

use Symfony\Component\Validator\Constraints\GroupSequence;

interface ValidatesInputInterface
{
    /**
     * Instanciates and calls a validator
     *
     * @return void
     */
    public function validate(string|GroupSequence|array|null $groups = null,);
}
