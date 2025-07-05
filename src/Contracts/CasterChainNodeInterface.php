<?php

namespace Nandan108\DtoToolkit\Contracts;

interface CasterChainNodeInterface
{
    /**
     * Returns the closure (or callable) that performs the transformation.
     */
    public function getClosure(): \Closure;

    /**
     * Returns a Closure that performs the transformation.
     * If $upstream ( u($v) ) is provided, the returned callable (f($v)) will wrap it: f(u($v)).
     *
     * @param ?\Closure $upstream the upstream closure to be used in the chain
     */
    public function getBuiltClosure(?\Closure $upstream): \Closure;

    public function __invoke(mixed $value): mixed;
}
