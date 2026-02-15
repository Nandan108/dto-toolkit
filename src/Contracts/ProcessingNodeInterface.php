<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/** @api */
interface ProcessingNodeInterface
{
    /**
     * Returns the closure (or callable) that performs the transformation.
     */
    public function getClosure(): \Closure;

    /**
     * Returns the name of the processing node.
     * This is used for debugging and error reporting purposes, and should ideally be a short,
     * human-readable identifier of the processing node (e.g. "CastTo\Int" or "Assert\Positive").
     *
     * @return truthy-string
     */
    public function getName(): string;

    /**
     * Returns a Closure that performs the transformation.
     * If $upstream ( u($v) ) is provided, the returned callable (f($v)) will wrap it: f(u($v)).
     *
     * @param ?\Closure $upstream the upstream closure to be used in the chain
     */
    public function getBuiltClosure(?\Closure $upstream): \Closure;

    public function __invoke(mixed $value): mixed;
}
