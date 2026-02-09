<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;

/**
 * Pads a string to a specified length
 * Uses str_pad or mb_str_pad (if available) to pad the string.
 *
 * @param positive-int $length  the desired length of the output string
 * @param string       $char    The character to use for padding. Default is a space.
 * @param int          $padType The type of padding. Can be STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH. Default is STR_PAD_RIGHT.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Pad extends CastBase
{
    public function __construct(
        int $length,
        string $char = ' ',
        int $padType = STR_PAD_RIGHT,
    ) {
        if ($length < 1) {
            throw new InvalidArgumentException('Pad caster: length must be >= 1.');
        }
        if (!\in_array($padType, [STR_PAD_LEFT, STR_PAD_RIGHT, STR_PAD_BOTH], true)) {
            throw new InvalidArgumentException('Pad caster: invalid pad type.');
        }

        parent::__construct([$length, $char, $padType]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        /** @var int $length */
        /** @var string $char */
        /** @var int $padType */
        [$length, $char, $padType] = $args;

        $value = $this->ensureStringable($value);

        // TODO: Remove this trick when bumping PHP requirement to 8.3+
        $pad = \function_exists('mb_str_pad') ? '\mb_str_pad' : '\str_pad';

        return $pad($value, $length, $char, $padType);
    }
}
