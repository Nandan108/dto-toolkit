<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

class GuardException extends ProcessingException
{
    public const DOMAIN = 'guard';
    /** @psalm-suppress PossiblyUnusedProperty */
    protected static string $template_prefix = self::DOMAIN.'.failed.';
    /** @psalm-suppress PossiblyUnusedProperty */
    protected static string $error_code = self::DOMAIN.'.failed';

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
        ?string $template_suffix = null,
        array $parameters = [],
        string | int | null $errorCode = self::DOMAIN.'.invalid_value',
        array $debug = [],
    ): self {
        return new self(
            template_suffix: 'invalid_value'.(null !== $template_suffix ? ".$template_suffix" : ''),
            parameters: [
                'type'          => get_debug_type($value),
            ] + $parameters,
            errorCode: $errorCode,
            httpCode: 422,
            debug: [
                'value'         => self::normalizeValueForDebug($value),
                'orig_value'    => $value,
            ] + $debug,
        );
    }
}
