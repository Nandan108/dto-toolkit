<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class JsonExtract extends CastBase
{
    public function __construct(
        public readonly string $path,
    ) {
        parent::__construct([$path]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        [$path] = $args;

        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: 'JSON decode error: '.$e->getMessage());
            }
        }

        if (!is_array($value)) {
            throw CastingException::castingFailure(static::class, $value, 'JsonExtract expects an array or a JSON string.');
        }

        foreach (explode('.', $path) as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                throw CastingException::castingFailure(static::class, $value, "Path segment `$key` not found in JSON structure.");
            }
            $value = $value[$key];
        }

        return $value;
    }
}
