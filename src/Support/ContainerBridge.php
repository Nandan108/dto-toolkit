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
 *
 * @api
 */
final class ContainerBridge
{
    private static ?ContainerInterface $container = null;

    /**
     * Manual bindings for types that cannot be resolved via the container, or for simple singletons.
     *
     * @var array<string, string|object>
     */
    private static array $manualBindings = [];

    /**
     * Registers a manual binding for a type. This is useful in various situations:
     * - When no DI container is available, and you want to specify how certain types should be resolved (e.g.,
     *   interfaces to concrete classes, or simple singletons).
     * - When you want to override the container's resolution for specific types.
     * - During testing, to mock certain dependencies without needing a full container setup.
     *
     * @param string        $abstract The abstract type or identifier to bind. This is the key used to resolve the type later.
     * @param string|object $concrete The concrete implementation or value to return when the type is resolved. This can be:
     *                                - An object instance: the same instance will be returned for every resolution (singleton).
     *                                - A closure: the closure will be invoked each time the type is resolved, allowing for dynamic resolution or factory behavior.
     *                                - A string: treated as a class name to be resolved via the container or reflection. This is useful for simple cases where you just want to specify a class to instantiate without needing a full container configuration. If the class constructor has required parameters, you must provide a factory Closure or a singleton instance instead.
     *                                Note on string values: since strings are also used as class names for container resolution, if you want to register a simple string value (not a class name), you must wrap it in a closure that returns the string. E.g.: ContainerBridge::register('app.locale', fn() => 'fr_CA');
     */
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

        // Try DI container first (prefer abstract id, then mapped concrete id)
        if (null !== self::$container) {
            if (self::$container->has($id)) {
                return self::$container->get($id);
            }
            if ($concrete !== $id && self::$container->has($concrete)) {
                return self::$container->get($concrete);
            }
        }

        // Fallback to reflection if no container is available.
        // This DOES NOT SUPPORT constructor arguments or any sort of auto-wiring like full-fledged DI containers.
        // It only works for classes with no constructor or with only optional parameters.
        if (class_exists($concrete)) {
            $ref = new \ReflectionClass($concrete);
            if ($ref->isInstantiable() && 0 === ($ref->getConstructor()?->getNumberOfRequiredParameters() ?? 0)) {
                self::$manualBindings[$id] = fn (): object => $ref->newInstance();

                return $ref->newInstance();
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
