<?php

namespace Nandan108\SymfonyDtoToolkit\Traits;

use LogicException;
use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;

trait NormalizesFromAttributes
{
    public function normalizeInbound(): static
    {
        foreach ($this->getAttributeCasts(outbound: false) as $prop => $method) {
            if (!empty($this->filled[$prop])) {
                $this->$prop = $method($this->$prop);
            }
        }

        return $this;
    }

    public function normalizeOutbound(array $props): array
    {
        $casts      = $this->getAttributeCasts(outbound: true);
        $normalized = [];

        foreach ($props as $prop => $value) {
            if (isset($casts[$prop])) {
                $normalized[$prop] = $this->{$casts[$prop]}($value);
            } else {
                $normalized[$prop] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Inspects property attributes and collects applicable casting methods.
     */
    protected function getAttributeCasts(bool $outbound = false): array
    {
        static $cache = [];

        if (isset($cache[$outbound])) {
            return $cache[$outbound];
        }

        $casts = [];

        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(CastTo::class) as $attr) {
                $attrInstance = $attr->newInstance();
                if ($attrInstance->outbound === $outbound) {
                    $method = 'castTo' . ucfirst($attrInstance->method);
                    if (!method_exists($this, $method)) {
                        throw new LogicException(
                            "Missing method '{$method}' for #[CastTo('{$attrInstance->method}')]" .
                            " on property \${$property->getName()} in " . static::class
                        );
                    }

                    $casts[$property->getName()] = fn($value) => $this->$method($value, ...$attrInstance->args);
                }
            }
        }

        return $cache[$outbound] = $casts;
    }


    /**
     * Convert a value to a DateTimeImmutable or null
     *
     * @param mixed $value
     * @return \DateTimeImmutable|null
     */
    protected function castToDateTimeOrNull(?string $value): ?\DateTimeImmutable
    {
        if (is_string($value) && $value !== '') {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Exception) {
                throw new LogicException('Invalid date format');
            }
        } elseif ($value instanceof \DateTimeImmutable) {
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
    protected function castToStringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Convert a value to an integer or null
     *
     * @param mixed $value
     * @return int|null
     */
    protected function castToIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }

    protected function castToFloatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }

    protected function castToBoolOrNull(mixed $value): ?bool
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
    protected function castToTrimmedString(mixed $value): string
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
    protected function castToArrayFromCSV(string $value, string $delimiter = ','): array
    {
        return array_map('trim', explode($delimiter, $value));
    }

    /**
     * Convert a value to an array or empty array
     *
     * @param mixed $value
     * @return array
     */
    protected function castToArrayOrEmpty(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }

        return [];
    }

}
