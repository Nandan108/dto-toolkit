<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Floating extends CastBase
{
    /**
     * @param string|null $decimalPoint
     *
     * Decimal point character to use for parsing the value.
     * If null, parsing will be done using the default decimal point character ('.').
     * If provided (including '.'), the value will first be normalized to keep only digits,
     * an optional leading minus sign, and the provided decimal point character.
     *
     * @psalm-suppress NullableProperty
     * @psalm-suppress PossiblyUnusedProperty
     */
    public function __construct(
        public readonly ?string $decimalPoint = null,
    ) {
        parent::__construct([$decimalPoint]);
        // noop to keep psalm happy
        [$this->decimalPoint];
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function cast(mixed $value, array $args): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = $this->ensureStringable($value);

        /** @var ?string $decimalPoint */
        [$decimalPoint] = $args;

        if (null !== $decimalPoint) {
            if ('' === $decimalPoint) {
                throw new InvalidArgumentException('CastTo\Floating: Decimal point cannot be empty.');
            }

            $value = $this->normalizeNumberString($value, $decimalPoint);
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw TransformException::expected($value, 'type.numeric');
    }

    public function normalizeNumberString(string $input, string $decimalPoint): string
    {
        // Escape the decimal point for safe regex embedding
        $escaped = preg_quote($decimalPoint, '/');

        // Keep only digits, minus signs and the decimal point
        $cleaned = preg_replace("/[^-0-9{$escaped}]/", '', $input) ?? '';

        // Normalize decimal
        if ('.' !== $decimalPoint) {
            $cleaned = str_replace($decimalPoint, '.', $cleaned);
        }

        // remove extra minus signs, and keep only last decimal point
        $cleaned = preg_replace('/(?!^)-|\.(?=.*\.)/', '', $cleaned) ?? '';

        return $cleaned;
    }
}
