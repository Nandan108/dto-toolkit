<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Trimmed extends CastBase
{
    public function __construct(
        string $characters = " \n\r\t\v\x00",
        string $where = 'both',
        bool $outbound = false,
    ) {
        parent::__construct($outbound, [$characters, $where]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?string
    {
        [$characters, $where] = $args;

        $v = is_string($value) ? $value : '';
        return match ($where) {
            'left'  => ltrim($v, $characters),
            'right' => rtrim($v, $characters),
            default => trim($v, $characters)
        };
    }
}
