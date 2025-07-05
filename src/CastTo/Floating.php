<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

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

    #[\Override]
    public function cast(mixed $value, array $args): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = $this->throwIfNotStringable($value);

        /** @var ?string $decimalPoint */
        [$decimalPoint] = $args;

        if (null !== $decimalPoint) {
            $value = $this->normalizeNumberString($value, $decimalPoint);
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: 'Expected numeric, but got '.gettype($value));
    }

    public function normalizeNumberString(string $input, string $decimalPoint): string
    {
        // Keep only digits, minus signs and decimal point characters
        $cleaned = $this->cleanUp($input, "/[^-0-9{$decimalPoint}]/");

        // Normalize decimal
        if ('.' !== $decimalPoint) {
            $cleaned = str_replace($decimalPoint, '.', $cleaned);
        }

        // remove extra minus signs, and keep only last decimal point
        return $this->cleanUp($cleaned, '/(?!^)-|\.(?=.*\.)/');
    }

    /**
     * Applies a regex replacement to the input string and returns the result.
     * Throws an exception if the result is null.
     *
     * @param non-empty-string $pattern
     *
     * @throws CastingException
     */
    private function cleanUp(string $input, string $pattern, string $replacement = ''): string
    {
        $cleaned = preg_replace($pattern, $replacement, $input);
        // What $input could make $cleaned null? Unlikely, but just in case...
        null !== $cleaned or throw CastingException::castingFailure(className: $this::class, operand: $input, messageOverride: 'Failed to normalize number string');

        return $cleaned;
    }
}
