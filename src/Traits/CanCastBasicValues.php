<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;

trait CanCastBasicValues
{
    /**
     * Convert a value to a DateTimeImmutable or null
     *
     * @param mixed $value
     * @return \DateTimeImmutable|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToDateTimeOrNull(mixed $value): ?\DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }
        if (is_string($value) && $value !== '') {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Exception) {
                return null;
                // throw new LogicException('Invalid date format');
            }
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        return null;
    }

    /**
     * Convert a value to a string or null
     *
     * @param mixed $value
     * @return string|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToStringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ||
            is_numeric($value) ||
            is_object($value) && method_exists($value, '__toString')
            ? (string)$value
            : null;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function dateTimeOrNull(): static
    {
        return new static('DateTimeOrNull');
    }

    /**
     * Convert a value to an integer or null
     *
     * @param mixed $value
     * @return int|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Convert a value to a float or null
     *
     * @param mixed $value
     * @return float|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToFloatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * Convert a value to a boolean or null
     *
     * @param mixed $value
     * @return bool|null
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToBoolOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    /**
     * Convert a value to an array or empty array
     *
     * @param mixed $value
     * @return string
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToTrimmedString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * Convert a string value to an array by splitting it by a delimiter (default is comma)
     *
     * Usage: #[CastTo('arrayFromCSV', args: [','])]
     *
     * @param string $value
     * @param non-empty-string $delimiter
     * @return array
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToArrayFromCSV(string $value, string $delimiter = ','): array
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return array_map('trim', explode($delimiter, $value));
    }

    /**
     * Convert a value to an array or empty array
     *
     * @param mixed $value
     * @return array
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function castToArrayOrEmpty(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }

        return [];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToRounded(mixed $value, int $precision = 0): ?float
    {
        if (is_numeric($value)) {
            return round((float)$value, $precision);
        }

        return null;
    }
    /** @psalm-suppress PossiblyUnusedMethod */
    public static function rounded(int $precision = 0): static
    {
        return new static('rounded', args: [$precision]);
    }

    // Static helpers for basic casting

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function stringOrNull(): static
    {
        return new static('StringOrNull');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function intOrNull(): static
    {
        return new static('IntOrNull');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function floatOrNull(): static
    {
        return new static('FloatOrNull');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function boolOrNull(): static
    {
        return new static('BoolOrNull');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function trimmedString(): static
    {
        return new static('TrimmedString');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function arrayFromCSV($delimiter = ','): static
    {
        return new static('ArrayFromCSV', args: [$delimiter]);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function arrayOrEmpty(): static
    {
        return new static('ArrayOrEmpty');
    }

}