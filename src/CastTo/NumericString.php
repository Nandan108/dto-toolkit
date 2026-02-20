<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Casts a numeric value to a formatted numeric string.
 * This is a number_format wrapper.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class NumericString extends CastBase implements CasterInterface
{
    /**
     * @param non-negative-int $decimals           Number of decimal points. Default is 0.
     * @param string           $decimalPoint       Character to use as decimal point. Default is '.'.
     * @param string           $thousandsSeparator Character to use as thousands separator. Default is empty string (no separator).
     *
     * @api
     */
    public function __construct(
        int $decimals = 0,
        string $decimalPoint = '.',
        string $thousandsSeparator = '',
    ) {
        parent::__construct([$decimals, $decimalPoint, $thousandsSeparator]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: int, 1: string, 2: string} $args */
        [$decimals, $decimalPoint, $thousandsSeparator] = $args;

        if (!is_numeric($value)) {
            throw TransformException::expected($value, 'type.numeric_string');
        }

        try {
            $floatVal = (float) $value;
            if (is_finite($floatVal)) {
                return number_format($floatVal, $decimals, $decimalPoint, $thousandsSeparator);
            }
        } catch (\Throwable $t) {
        }
        // If we get here, it means the value is numeric but cannot be represented as a finite float
        // (e.g. very large numbers, INF, NAN)
        throw TransformException::reason(
            value: $value,
            template_suffix: 'numeric_string.invalid',
            parameters: [
                'decimals'           => $decimals,
                'decimalPoint'       => $decimalPoint,
                'thousandsSeparator' => $thousandsSeparator,
            ],
            errorCode: 'numeric_string',
        );
    }
}
