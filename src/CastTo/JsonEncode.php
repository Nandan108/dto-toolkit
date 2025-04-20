<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class JsonEncode extends CastBase implements CasterInterface
{
    public function __construct(int $flags = 0, int $depth = 512, bool $outbound = false)
    {
        parent::__construct($outbound, [$flags, $depth]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        /** @var int $flags */
        [$flags, $depth] = $args;
        $flags = $flags & ~JSON_THROW_ON_ERROR;

        $json = json_encode($value, $flags, $depth);

        if (false === $json) {
            $error = json_last_error_msg();
            throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: "Failed to cast value to JSON: $error");
        }

        return $json;
    }
}
