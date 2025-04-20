<?php

namespace Nandan108\DtoToolkit\Core;

use Attribute;
use Nandan108\DtoToolkit\Attribute\Injected;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

abstract class CastBase extends CastTo implements CasterInterface
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
    public function inject(): void
    {
        foreach ((new \ReflectionClass($this))->getProperties() as $prop) {
            if (!$prop->getAttributes(Injected::class)) {
                continue;
            }
            /** @psalm-suppress UndefinedMethod */
            $type = $prop->getType()?->getName();
            if (!$type) {
                throw new \RuntimeException("Cannot inject untyped property {$prop->getName()}");
            }
            $value = $this->resolveFromContainer($type);
            $prop->setValue($this, $value);
        }
    }

    protected function is_stringable(mixed $val): bool
    {
        return is_string($val)
            || is_numeric($val)
            || is_object($val) && method_exists($val, '__toString');
    }

    protected function throwIfNotStringable(mixed $value, string $expected = 'numeric, string or Stringable'): string
    {
        $this->is_stringable($value)
            or throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: "Expected: $expected, but got ".gettype($value));

        return (string) $value;
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
