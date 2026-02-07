<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a JSON string (optionally restricted to specific JSON types).
 *
 * @psalm-type JsonType = 'object'|'array'|'number'|'string'|'bool'|'null'
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Json extends ValidatorBase
{
    /** @var list<JsonType> */
    private const ALLOWED_TYPES = ['object', 'array', 'number', 'string', 'bool', 'null'];

    /**
     * @param list<JsonType> $allowedTypes
     */
    public function __construct(array $allowedTypes = [])
    {
        foreach ($allowedTypes as $type) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!\in_array($type, self::ALLOWED_TYPES, true)) {
                /** @var string $type */
                throw new InvalidConfigException("Json validator: unknown JSON type '{$type}'.");
            }
        }

        parent::__construct([$allowedTypes]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value);

        // TODO: Remove this trick when bumping PHP requirement to 8.3+
        $json_validate = \function_exists('json_validate') ? 'json_validate' : [self::class, 'polyfillJsonValidate'];

        $valid = $json_validate($value);
        if (!$valid) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'json.invalid',
                errorCode: 'validate.json.invalid',
            );
        }

        /** @var list<JsonType> $allowedTypes */
        $allowedTypes = $args[0];
        if (!$allowedTypes) {
            return;
        }

        $detectedType = self::detectType($value);
        if (null === $detectedType || !\in_array($detectedType, $allowedTypes, true)) {
            throw GuardException::reason(
                value: $value,
                template_suffix: 'json.type_not_allowed',
                parameters: [
                    'allowedTypes' => $allowedTypes,
                    'detectedType' => $detectedType ?? 'unknown',
                ],
                errorCode: 'validate.json.type_not_allowed',
            );
        }
    }

    private static function polyfillJsonValidate(string $value): bool
    {
        /** @psalm-suppress UnusedFunctionCall */
        json_decode($value);

        return \JSON_ERROR_NONE === json_last_error();
    }

    private static function detectType(string $value): ?string
    {
        $trimmed = ltrim($value);
        if ('' === $trimmed) {
            return null;
        }

        $first = $trimmed[0];

        return match (true) {
            '{' === $first                   => 'object',
            '[' === $first                   => 'array',
            '"' === $first                   => 'string',
            't' === $first || 'f' === $first => 'bool',            'n' === $first => 'null',            '-' === $first || ctype_digit($first) => 'number',            default => null,
        };
    }
}
