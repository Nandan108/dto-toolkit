<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is one of the allowed choices (strict or loose).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class In extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function __construct(array $choices, bool $strict = true)
    {
        if ([] === $choices) {
            throw new InvalidArgumentException('In validator requires at least one choice.');
        }
        parent::__construct([$choices, $strict]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: array, 1: bool} $args */
        [$choices, $strict] = $args;

        if (!in_array($value, $choices, $strict)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'not_in',
                parameters: ['allowed' => $choices],
            );
        }
    }
}
