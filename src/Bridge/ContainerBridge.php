<?php

namespace Nandan108\DtoToolkit\Bridge;

use Psr\Container\ContainerInterface;

final class ContainerBridge
{
    private static ?ContainerInterface $container = null;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function get(string $id): mixed
    {
        return self::$container?->get($id);
    }

    public static function has(string $id): bool
    {
        return self::$container?->has($id) ?? false;
    }
}
