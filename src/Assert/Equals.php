<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates equality against a scalar (strict or loose).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Equals extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(mixed $value, bool $strict = true)
    {
        parent::__construct([$value, $strict]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$expected, $strict] = $args;
        $op = $strict ? '===' : '==';

        $matches = CompareTo::compareValues(
            left: $value,
            right: $expected,
            op: $op,
            leftIsValue: true,
            rightIsValue: false,
        );

        if (!$matches) {
            throw GuardException::expected(
                operand: $value,
                expected: 'value.equals',
                templateSuffix: 'equals',
                parameters: [
                    'expectedValue' => $expected,
                    'strict'        => $strict,
                ],
            );
        }
    }
}
