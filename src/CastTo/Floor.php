<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Floor extends CastBase implements CasterInterface
{
    public function __construct(bool $nullable = false, bool $outbound = false)
    {
        parent::__construct($outbound, [$nullable]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?int
    {
        [$nullable] = $args;

        if (!is_numeric($value)) {
            return $nullable ? null : 0;
        }

        return (int)floor((float)$value);
    }
}
