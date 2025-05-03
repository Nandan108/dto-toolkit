<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Support\ContainerBridge;

/**
 * Basic implementation of the Injectable interface.
 */
trait IsInjectable
{
    /**
     * Uses ContainerBridge::get($type); to populate instance properties that are marked with #[Inject].
     *
     * @throws \RuntimeException
     *
     * @psalm-suppress MethodSignatureMismatch
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
            $value = ContainerBridge::get($type);
            /** @psalm-suppress UnusedMethodCall */
            $prop->setAccessible(true);
            $prop->setValue($this, $value);
        }

        return $this;
    }
}
