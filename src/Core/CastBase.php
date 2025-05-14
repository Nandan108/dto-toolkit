<?php

namespace Nandan108\DtoToolkit\Core;

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
    public function __construct(array $args = [], array $constructorArgs = [])
    {
        parent::__construct(
            methodOrClass: static::class,
            args: $args,
            constructorArgs: $constructorArgs,
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
    protected function throwIfNotStringable(mixed $value, string $expected = 'numeric, string or Stringable', bool $expectNonEmpty = false): string
    {
        $expected = "Expected: $expected (got ".gettype($value).')';

        if ($this->is_stringable($value)) {
            $value = (string) $value;
            if ($expectNonEmpty && '' === trim($value)) {
                throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: $expected);
            }

            return $value;
        }

        throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: "Expected: $expected, but got ".gettype($value));
    }

    /**
     * Utility function: Throw if the value is not numeric.
     */
    protected function throwIfNotNumeric(mixed $value, string $expected = 'numeric'): void
    {
        if (!is_numeric($value)) {
            throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: "Expected: $expected, but got non-numeric ".gettype($value));
        }
    }

    /**
     * Utility function for use in caster constructors.
     * Throws if the value is not a DateTimeInterface.
     *
     * @throws \RuntimeException
     */
    protected function throwIfExtensionNotLoaded(string $extensionName): void
    {
        $classShortName = fn (): string => (new \ReflectionClass(static::class))->getShortName();
        extension_loaded($extensionName) || throw new \RuntimeException("Extension $extensionName is required for ".$classShortName());
    }

    /**
     * Transform $value according to caster logic and arguments, and return it.
     *
     * @param mixed[] $args passed from Attribute constructor
     */
    #[\Override]
    abstract public function cast(mixed $value, array $args): mixed;
}
