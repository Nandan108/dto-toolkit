<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/** @api */
interface NodeResolverInterface
{
    /**
     * Resolve a class name to a CasterInterface/ValidatorInterface instance or a closure.
     *
     * @param string $methodOrClass   the class name to resolve
     * @param array  $constructorArgs optional constructor arguments for the class
     */
    public function resolve(
        string $methodOrClass,
        array $args = [],
        array $constructorArgs = [],
    ): \Closure | CasterInterface | ValidatorInterface;
}
