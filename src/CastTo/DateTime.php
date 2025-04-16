<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class DateTime extends CastBase
{
    public function __construct(string $format = 'Y-m-d H:i:s', bool $outbound = false)
    {
        parent::__construct($outbound, [$format]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): \DateTimeImmutable|false|null
    {
        [$format] = $args;
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        return \DateTimeImmutable::createFromFormat($format, (string)$value) ?: null;
    }
}
