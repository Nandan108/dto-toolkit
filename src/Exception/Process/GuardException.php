<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

class GuardException extends ProcessingException
{
    public const DOMAIN = 'processing.guard';
    /** @psalm-suppress PossiblyUnusedProperty */
    protected static string $template_prefix = 'processing.guard.failed.';
    /** @psalm-suppress PossiblyUnusedProperty */
    protected static string $error_code = 'guard.failed';

    /**
     * Guard failed with value information.
     * When:
     * - the value is unacceptable
     * - AND the validator is value-specific
     * - AND the rule applies to any Validator (string length, numeric range, email, etc.).
     *
     * @param non-empty-string $template_suffix
     */
    public static function invalidValue(
        mixed $value,
        string $template_suffix = 'invalid_value',
        array $parameters = [],
        string | int | null $errorCode = 'guard.invalid_value',
        array $debug = [],
    ): self {
        return new self(
            template_suffix: $template_suffix,
            parameters: [
                'type'          => get_debug_type($value),
            ] + $parameters,
            errorCode: $errorCode,
            httpCode: 422,
            debug: [
                'value'         => self::prepareOperandForDebug($value),
                'orig_value'    => $value,
            ] + $debug,
        );
    }

    /**
     * Guard failed due to missing value (null or empty).
     */
    public static function required(
        mixed $what,
        mixed $badValue,
        array $parameters = [],
        string | int | null $errorCode = 'guard.required',
    ): static {
        /** @var static */
        return static::reason(
            value: $badValue,
            template_suffix: "required.$what",
            parameters: $parameters,
            errorCode: $errorCode,
        );
    }
}
