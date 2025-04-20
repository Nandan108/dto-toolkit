<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CsvFromArray extends CastBase
{
    public function __construct(string $separator = ',', bool $outbound = false)
    {
        parent::__construct($outbound, [$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?string {
        [$separator] = $args;

        return is_array($value)
            ? implode($separator, $value)
            : '';
    }
}
