<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Trimmed extends CastBase
{
    /** @api */
    public function __construct(
        string $characters = " \n\r\t\v\x00",
        string $where = 'both',
    ) {
        parent::__construct([$characters, $where]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        [$characters, $where] = $args;

        $v = $this->ensureStringable($value);

        return match ($where) {
            'left'  => ltrim($v, $characters),
            'right' => rtrim($v, $characters),
            default => trim($v, $characters),
        };
    }
}
