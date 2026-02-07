<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is null or non-null based on expectation.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsNull extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(bool $expect = true)
    {
        parent::__construct([$expect]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$expect] = $args;

        if ($expect) {
            if (null !== $value) {
                throw GuardException::expected(
                    operand: $value,
                    expected: 'null',
                );
            }

            return;
        }

        if (null === $value) {
            throw GuardException::required(
                what: 'non_null_value',
                badValue: $value,
            );
        }
    }
}
