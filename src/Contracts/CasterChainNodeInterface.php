<?php

namespace Nandan108\DtoToolkit\Contracts;

interface CasterChainNodeInterface
{
    public function getClosure(): callable;

    public function getBuiltClosure(?callable $upstream): callable;

    public function __invoke(mixed $value): mixed;
}
