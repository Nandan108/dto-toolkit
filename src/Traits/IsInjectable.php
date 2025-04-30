<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Bridge\ContainerBridge;

/**
 * Basic implementation of the Injectable interface.
 */
trait IsInjectable
{
    /**
     * Uses $this->resolveFromContainer($type) to populate instance properties that are marked with #[Inject].
     *
     * @throws \RuntimeException
     */
    #[\Override] // implements Injectable
    public function inject(): static
    {
        $injectableProps = array_filter(
            (new \ReflectionClass($this))->getProperties(),
            static fn (\ReflectionProperty $prop) => $prop->getAttributes(Inject::class)
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

        return $this;
    }

    /**
     * Resolve a type using the ContainerBridge.
     * This method is a good place to override in adapter packages.
     *
     * @throws \LogicException
     */
    protected function resolveFromContainer(string $type): mixed
    {
        return ContainerBridge::get($type);
    }
}
