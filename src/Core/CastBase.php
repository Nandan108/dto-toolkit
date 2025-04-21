<?php

namespace Nandan108\DtoToolkit\Core;

use Attribute;
use Nandan108\DtoToolkit\Attribute\Injected;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * Base class for all Caster classes.
 */
abstract class CastBase extends CastTo implements CasterInterface, Injectable
{
    /**
     * Constructs an instance of a Caster class.
     *
     * @param array $args     should contain any argument values used to parameterize caster behavior
     * @param bool  $outbound indicates whether this casting should be done after validation (inbound) or before outputting
     */
    public function __construct(bool $outbound = false, array $args = [])
    {
        parent::__construct(
            methodOrClass: static::class,
            outbound: $outbound,
            args: $args,
        );
    }

    /**
     * Uses $this->resolveFromContainer($type) to populate instance properties that are marked with #[Injected].
     *
     * @throws \RuntimeException
     */
    #[\Override]
    public function inject(): void
    {
        $injectableProps = array_filter(
            (new \ReflectionClass($this))->getProperties(),
            static fn (\ReflectionProperty $prop) => $prop->getAttributes(Injected::class)
        );
        foreach ($injectableProps as $prop) {
            /** @psalm-suppress UndefinedMethod */
            $type = $prop->getType()?->getName();
            if (!$type) {
                throw new \RuntimeException("Cannot inject untyped property {$prop->getName()}");
            }
            $value = $this->resolveFromContainer($type);
            $prop->setValue($this, $value);
        }
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
     * @param string      $expected    the expected type
     * @param string|null $ctorArgName the name of the constructor argument, if checking a caster contructor argument value
     */
    protected function throwIfNotStringable(mixed $value, string $expected = 'numeric, string or Stringable', ?string $ctorArgName = null): string
    {
        if ($this->is_stringable($value)) {
            return (string) $value;
        }

        if (null === $ctorArgName) {
            throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: "Expected: $expected, but got ".gettype($value));
        } else {
            throw new \InvalidArgumentException("Constructor argument \$$ctorArgName expects $expected, but got ".gettype($value));
        }
    }

    /**
     * Resolve a type from a DI container.
     *
     * @throws \LogicException
     */
    protected function resolveFromContainer(string $type): mixed
    {
        throw new \LogicException("No container resolver defined in Core. Unable to resolve type '$type'.");
    }

    /**
     * Transform $value according to caster logic and arguments, and return it.
     *
     * @param mixed[] $args passed from Attribute constructor
     */
    #[\Override]
    abstract public function cast(mixed $value, array $args = []): mixed;
}
