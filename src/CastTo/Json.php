<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Json extends CastBase
{
    public function __construct(int $flags = 0, int $depth = 512)
    {
        parent::__construct([$flags, $depth]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: int, 1: int<1, 2147483647>} $args */
        [$flags, $depth] = $args;

        $encoded = json_encode($value, $flags & ~JSON_THROW_ON_ERROR, $depth);

        if (false !== $encoded) {
            return $encoded;
        }

        throw TransformException::reason(
            value: $value,
            template_suffix: 'json.encoding_failed',
            errorCode: 'transform.json',
            debugExtras: ['message' => json_last_error_msg()],
        );
    }
}
