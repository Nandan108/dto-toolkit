<?php

namespace Nandan108\DtoToolkit\Contracts;

interface CasterResolverInterface
{
    /**
     * Resolve a class name to a CasterInterface instance or a closure.
     *
     * @param string $className The class name to resolve.
     * @param array $constructorArgs Optional constructor arguments for the class.
     */
    public function resolve(string $className, ?array $constructorArgs = []): \Closure|CasterInterface;
}
