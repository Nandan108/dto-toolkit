<?php

namespace Nandan108\DtoToolkit\Support;

use Psr\Container\ContainerInterface;

/**
 * ContainerBridge is a static bridge to a DI container.
 *
 * Normally in a frameworked app, we’d avoid static access to containers (Service Locator pattern).
 * But since DTOT is a package meant to adapt to any host, this pattern
 * - Let us opt into DI if available
 * - Keeps DTOs usable even in non-containerized code (e.g., CLI tools, microservices)
 */
final class ContainerBridge
{
    private static ?ContainerInterface $container = null;
    private static array $manualBindings = [];

    public static function register(string $abstract, string|object $concrete): void
    {
        self::$manualBindings[$abstract] = $concrete;
    }

    public static function clearBindings(): void
    {
        self::$manualBindings = [];
    }

    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function get(string $id): mixed
    {
        if (null !== self::$container) {
            return self::$container->get($id);
        }

        // Try registered binding
        $concrete = self::$manualBindings[$id] ?? $id;

        if (is_object($concrete)) {
            // Return singleton or result of closure
            return $concrete instanceof \Closure ? $concrete() : $concrete;
        }

        // Fallback to reflection if no container is available -- this is a last resort that should be avoided in production.
        // Allows for instantiation of classes without constructor arguments
        if (class_exists($concrete)) {
            $ref = new \ReflectionClass($concrete);
            if ($ref->isInstantiable() && 0 === ($ref->getConstructor()?->getNumberOfRequiredParameters() ?? 0)) {
                self::$manualBindings[$id] = fn (): object => new $concrete();

                return new $concrete();
            }
        }

        throw new \LogicException('No DI container was configured, unable to resolve type '.$concrete.'.');
    }

    public static function has(string $id): bool
    {
        return self::$container?->has($id) ?? false;
    }
}
