<?php

namespace Nandan108\DtoToolkit\Exception;

use RuntimeException;

final class CastingException extends RuntimeException
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $method = null;
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $className = null;
    /** @psalm-suppress PossiblyUnusedProperty */
    public mixed $operand = null;

    public static function unresolved(string $methodOrClass): self
    {
        $e         = new self("Caster '{$methodOrClass}' could not be resolved.", 501);
        $e->method = $methodOrClass;
        return $e;
    }

    public static function casterInterfaceNotImplemented(string $className): self
    {
        $e            = new self("Class '{$className}' does not implement the CasterInterface.");
        $e->className = $className;
        return $e;
    }
}
