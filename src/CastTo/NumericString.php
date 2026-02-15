<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class NumericString extends CastBase implements CasterInterface
{
    /** @api */
    public function __construct(
        public readonly int $decimals = 0,
        public readonly string $decimalPoint = '.',
        public readonly string $thousandsSeparator = '',
    ) {
        parent::__construct([$decimals, $decimalPoint, $thousandsSeparator]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        [$decimals, $decimalPoint, $thousandsSeparator] = $args;

        if (!is_numeric($value)) {
            throw TransformException::expected($value, 'type.numeric_string');
        }

        return number_format((float) $value, (int) $decimals, $decimalPoint, $thousandsSeparator);
    }
}
