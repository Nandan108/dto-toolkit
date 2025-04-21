<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CsvFromArray extends CastBase
{
    public function __construct(string $separator = ',', bool $outbound = false)
    {
        parent::__construct($outbound, [$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?string
    {
        [$separator] = $args;

        if (!is_array($value)) {
            throw CastingException::castingFailure(className: self::class, operand: $value, messageOverride: 'Expected array, but got '.gettype($value));
        }

        return implode($separator, $value);
    }
}
