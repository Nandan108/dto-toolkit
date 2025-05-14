<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class FromBase64 extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        $value = $this->throwIfNotStringable($value);

        $decoded = base64_decode($value, true);

        if (false === $decoded) {
            throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: 'Base64 decode failed: invalid encoding.');
        }

        return $decoded;
    }
}
