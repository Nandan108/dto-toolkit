<?php

namespace Nandan108\DtoToolkit\Bridge;

use Psr\Container\ContainerInterface;

final class ContainerBridge
{
    private static ?ContainerInterface $container = null;

    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function get(string $id): mixed
    {
        if (null === self::$container) {
            throw new \LogicException('No DI container was configured, unable to resolve type '.$id.'.');
        }

        return self::$container->get($id);
    }

    public static function has(string $id): bool
    {
        return self::$container?->has($id) ?? false;
    }
}
