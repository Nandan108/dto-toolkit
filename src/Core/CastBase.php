<?php

namespace Nandan108\DtoToolkit\Core;

use Attribute;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\IsInjectable;

/**
 * Base class for all Caster classes.
 */
abstract class CastBase extends CastTo implements CasterInterface, Injectable
{
    use IsInjectable;

    /**
     * Constructs an instance of a Caster class.
     *
     * @param array $args should contain any argument values used to parameterize caster behavior
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __construct(array $args = [])
    {
        parent::__construct(
            methodOrClass: static::class,
            args: $args,
        );
    }

    /**
     * Utility function: Check if the value is stringable.
     */
    protected function is_stringable(mixed $val): bool
    {
        return is_string($val)
            || is_numeric($val)
            || is_object($val) && method_exists($val, '__toString');
    }

    /**
     * Utility function: Throw if the value is not stringable.
     *
     * @param string $expected the expected type
     */
    protected function throwIfNotStringable(mixed $value, string $expected = 'numeric, string or Stringable'): string
    {
        if ($this->is_stringable($value)) {
            return (string) $value;
        }

        throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: "Expected: $expected, but got ".gettype($value));
    }

    /**
     * Transform $value according to caster logic and arguments, and return it.
     *
     * @param mixed[] $args passed from Attribute constructor
     */
    #[\Override]
    abstract public function cast(mixed $value, array $args): mixed;
}
