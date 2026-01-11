<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is one of the allowed choices (strict or loose).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class In extends ValidatorBase
{
    /**
     * @param array<int|string, mixed> $choices
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(array $choices, bool $strict = true)
    {
        if ([] === $choices) {
            throw new InvalidConfigException('In validator requires at least one choice.');
        }
        parent::__construct([$choices, $strict]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$choices, $strict] = $args;

        if (!in_array($value, $choices, $strict)) {
            $allowedValues = json_encode(array_values($choices), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $allowedValues = 'unrepresentable values';
            }

            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'in.not_allowed',
                parameters: ['allowed' => $allowedValues],
                methodOrClass: self::class,
            );
        }
    }
}
