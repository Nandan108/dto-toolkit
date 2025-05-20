<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Support\EntityAccessorHelper;

/**
 * Extracts a nested value from an array/object structure using a dot-delimited path.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Extract extends CastBase
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public function __construct(string $path)
    {
        parent::__construct([$path]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        [$path] = $args;

        if (!is_array($value) && !is_object($value)) {
            throw CastingException::castingFailure(static::class, $value, 'CastTo\Extract expects an array or object');
        }

        $keyStack = '';
        foreach (explode('.', $path) as $key) {
            if (is_array($value)) {
                if (!array_key_exists($key, $value)) {
                    throw CastingException::castingFailure(static::class, $value, "Path segment $keyStack.`$key` not found in array.");
                }
                $value = $value[$key];
            } elseif ($value instanceof \ArrayAccess) {
                if (!$value->offsetExists($key)) {
                    throw CastingException::castingFailure(static::class, $value, "Path segment $keyStack.`$key` not found in ArrayAccess object.");
                }
                $value = $value[$key];
            } elseif (is_object($value)) {
                $getter = EntityAccessorHelper::getEntityGetterMap($value, [$key], true)[$key] ?? null;
                if (!$getter) {
                    throw CastingException::castingFailure(static::class, $value, "Path segment $keyStack.`$key` is not accessible in object of type ".get_class($value));
                }
                $value = $getter($value);
            } else {
                $type = get_debug_type($value);
                throw CastingException::castingFailure(static::class, $value, "Unexpected type `$type` at $keyStack.`$key` — expected array or object.");
            }

            $keyStack .= ($keyStack ? '.' : '').$key;
        }

        return $value;
    }
}
