<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class FromBase64 extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        $value = $this->ensureStringable($value);

        $decoded = base64_decode($value, true);

        if (false === $decoded) {
            throw TransformException::reason(
                value: $value,
                template_suffix: 'base64.decode_failed',
                errorCode: 'transform.base64',
            );
        }

        return $decoded;
    }
}
