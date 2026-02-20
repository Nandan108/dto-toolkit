<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Trimmed extends CastBase
{
    /**
     * @param string                $characters The characters to trim (default: " \n\r\t\v\x00")
     * @param 'left'|'right'|'both' $where      Where to trim: 'left', 'right', or 'both'
     *
     * @api
     */
    public function __construct(
        string $characters = " \n\r\t\v\x00",
        string $where = 'both',
    ) {
        /** @psalm-suppress DocblockTypeContradiction, InvalidCast */
        if (!in_array($where, ['left', 'right', 'both'], true)) {
            throw new \InvalidArgumentException("Invalid value for 'where' parameter: '$where'. Allowed values are 'left', 'right', or 'both'.");
        }

        parent::__construct([$characters, $where]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        /** @var string $characters */
        [$characters, $where] = $args;

        $v = $this->ensureStringable($value);

        return match ($where) {
            'left'  => ltrim($v, $characters),
            'right' => rtrim($v, $characters),
            default => trim($v, $characters),
        };
    }
}
