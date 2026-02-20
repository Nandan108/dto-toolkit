<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Support;

final class CacheKeySerializer
{
    /** @internal */
    public static function serialize(mixed $value): string
    {
        return self::encode(self::normalize($value));
    }

    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            null === $value                      => ['__null__' => true],
            is_scalar($value)                    => $value,
            $value instanceof \BackedEnum        => ['__enum__' => $value::class, 'value' => $value->value],
            $value instanceof \UnitEnum          => ['__enum__' => $value::class, 'name' => $value->name],
            $value instanceof \DateTimeInterface => ['__datetime__' => $value::class, 'value' => $value->format(\DateTimeInterface::ATOM)],
            \is_object($value)                   => ['__object__' => $value::class, 'id' => spl_object_id($value)],
            \is_resource($value)                 => ['__resource__' => get_resource_type($value)],
            \is_array($value)                    => array_map(fn ($item): mixed => self::normalize($item), $value),
            default                              => null,
        };
    }

    private static function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '[]';
        }
    }
}
