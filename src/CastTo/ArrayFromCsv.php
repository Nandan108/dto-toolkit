<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayFromCsv extends CastBase
{
    public function __construct(string $separator = ',', bool $outbound = false)
    {
        parent::__construct($outbound, [$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []):  array {
        [$separator] = $args;

        return is_string($value)
            ? explode($separator, $value)
            : [];
    }
}
