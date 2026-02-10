<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Support;

use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
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

    public static function register(string $abstract, string | object $concrete): void
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
        // Try registered binding
        $concrete = self::$manualBindings[$id] ?? $id;

        if (is_object($concrete)) {
            // Return singleton or result of closure
            return $concrete instanceof \Closure ? $concrete() : $concrete;
        }

        // Try DI container, if it's set, let it resolve the type
        if (null !== self::$container) {
            return self::$container->get($id);
        }

        // Fallback to reflection if no container is available.
        // This DOES NOT SUPPORT constructor arguments or any sort of auto-wiring like full-fledged DI containers.
        // It only works for classes with no constructor or with only optional parameters.
        if (class_exists($concrete)) {
            $ref = new \ReflectionClass($concrete);
            if ($ref->isInstantiable() && 0 === ($ref->getConstructor()?->getNumberOfRequiredParameters() ?? 0)) {
                self::$manualBindings[$id] = fn (): object => new $concrete();

                return new $concrete();
            }
        }

        throw new InvalidConfigException('No DI container was configured, unable to resolve type '.$concrete.'.');
    }

    public static function has(string $id): bool
    {
        // Check manual bindings first, then container if available
        return \array_key_exists($id, self::$manualBindings)
            ? true
            : self::$container?->has($id) ?? false;
    }

    public static function tryGet(string $id): mixed
    {
        return self::has($id) ? self::get($id) : null;
    }
}
