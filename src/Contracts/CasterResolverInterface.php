<?php

namespace Nandan108\SymfonyDtoToolkit\Contracts;

interface CasterResolverInterface
{
    public function resolve(string $casterClass): object;
}
