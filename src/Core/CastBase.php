<?php

namespace Nandan108\DtoToolkit\Core;

use Attribute;
use Nandan108\DtoToolkit\Attribute\Injected;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Bridge\ContainerBridge;

abstract class CastBase extends CastTo implements CasterInterface
{
    /**
     * Constructs an instance of a Caster class
     * @param array $args should contain any argument values used to parameterize caster behavior
     * @param bool $outbound indicates whether this casting should be done after validation (inbound) or before outputting
     */
    public function __construct(bool $outbound = false, array $args = []) {
        parent::__construct(
            methodOrClass: static::class,
            outbound: $outbound,
            args: $args
        );
    }

    /**
     * Uses $this->resolveFromContainer($type) to populate instance properties that are marked with #[Injected]
     *
     * @throws \RuntimeException
     * @return void
     */
    public function inject(): void
    {
        foreach ((new \ReflectionClass($this))->getProperties() as $prop) {
            if (! $prop->getAttributes(Injected::class)) continue;
            /** @psalm-suppress UndefinedMethod */
            $type = $prop->getType()?->getName();
            if (! $type) throw new \RuntimeException("Cannot inject untyped property {$prop->getName()}");
            $value = $this->resolveFromContainer($type);
            $prop->setValue($this, $value);
        }
    }

    /**
     * Resolve a type from a DI container.
     *
     * @param string $type
     * @throws \LogicException
     * @return mixed
     */
    protected function resolveFromContainer(string $type): mixed
    {
        throw new \LogicException("No container resolver defined in Core. Unable to resolve type '$type'.");
    }

    /**
     * Transform $value according to caster logic and arguments, and return it.
     *
     * @param mixed $value
     * @param mixed[] $args passed from Attribute constructor
     * @return mixed
     */
    #[\Override]
    abstract public function cast(mixed $value, array $args = []): mixed;
}
