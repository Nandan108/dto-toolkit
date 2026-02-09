<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a numeric value is within a configured range.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Range extends ValidatorBase
{
    /** psalm-suppress PossiblyUnusedMethod */
    public function __construct(?float $min = null, ?float $max = null, bool $inclusive = true)
    {
        if (null === $min && null === $max) {
            throw new InvalidArgumentException('Range validator requires at least one of min or max.');
        }
        if (null !== $min && null !== $max && $min > $max) {
            throw new InvalidArgumentException('Range validator requires min to be less than or equal to max.');
        }
        parent::__construct([$min, $max, $inclusive]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!\is_int($value) && !\is_float($value)) {
            throw GuardException::expected(
                operand: $value,
                expected: 'type.number',
            );
        }

        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0:?float, 1:?float, 2:bool} $args */
        [$min, $max, $inclusive] = $args;

        $below_min = null !== $min && ($inclusive ? $value < $min : $value <= $min);
        $above_max = null !== $max && ($inclusive ? $value > $max : $value >= $max);
        $error = $below_min ? 'below_min' : ($above_max ? 'above_max' : null);

        if (null !== $error) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: "number.$error",
                parameters: ['min' => $min, 'max' => $max, 'inclusive' => $inclusive],
                errorCode: 'guard.range',
            );
        }
    }
}
