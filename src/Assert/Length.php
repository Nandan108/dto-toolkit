<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates string or array length constraints.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Length extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    /** @api */
    public function __construct(?int $min = null, ?int $max = null)
    {
        if (null === $min && null === $max) {
            throw new InvalidArgumentException('Length validator requires at least one of min or max.');
        }
        if (null !== $min && null !== $max && $min > $max) {
            throw new InvalidArgumentException('Length validator requires min to be less than or equal to max.');
        }
        parent::__construct([$min, $max]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var ?int $min */
        /** @var ?int $max */
        [$min, $max] = $args + [null, null];

        $type = gettype($value);

        $len = match ($type) {
            'array'  => \count($value),
            'string' => \mb_strlen($value),
            default  => throw GuardException::expected(
                operand: $value,
                expected: ['type.string', 'type.array'],
            ),
        };

        $min_ok = null === $min ? true : $len >= $min;
        $max_ok = null === $max ? true : $len <= $max;

        if (!$min_ok) {
            throw GuardException::reason(
                value: $value,
                template_suffix: "{$type}_length.below_min",
                parameters: ['min' => $min],
                errorCode: 'guard.range',
            );
        }

        if (!$max_ok) {
            throw GuardException::reason(
                value: $value,
                template_suffix: "{$type}_length.above_max",
                parameters: ['max' => $max],
                errorCode: 'guard.range',
            );
        }
    }
}
