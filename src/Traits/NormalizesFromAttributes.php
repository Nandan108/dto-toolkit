<?php

namespace Nandan108\SymfonyDtoToolkit\Traits;

use LogicException;
use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;

trait NormalizesFromAttributes
{
    public function normalizeInbound(): void
    {
        $casters = CastTo::getCastingClosureMap($this, outbound: false);

        foreach ($casters as $prop => $method) {
            if (!empty($this->_filled[$prop])) {
                $this->$prop = $method($this->$prop);
            }
        }
    }

    public function normalizeOutbound(array $props): array
    {
        $casters    = CastTo::getCastingClosureMap($this, outbound: true);
        $normalized = [];

        foreach ($props as $prop => $value) {
            if (isset($casters[$prop])) {
                $normalized[$prop] = $casters[$prop]($value);
            } else {
                $normalized[$prop] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Convert a value to a DateTimeImmutable or null
     *
     * @param mixed $value
     * @return \DateTimeImmutable|null
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
     */
    public function castToStringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ||
            is_numeric($value) ||
            is_object($value && method_exists($value, '__toString'))
            ? (string)$value
            : null;
    }

    /**
     * Convert a value to an integer or null
     *
     * @param mixed $value
     * @return int|null
     */
    public function castToIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }

    public function castToFloatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

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
     * @return array
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
     * @param string $delimiter
     * @return array
     */
    public function castToArrayFromCSV(string $value, string $delimiter = ','): array
    {
        return array_map('trim', explode($delimiter, $value));
    }

    /**
     * Convert a value to an array or empty array
     *
     * @param mixed $value
     * @return array
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

}
