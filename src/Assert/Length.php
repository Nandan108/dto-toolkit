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
    public function __construct(?int $min = null, ?int $max = null)
    {
        if (null === $min && null === $max) {
            throw new InvalidArgumentException('Length validator requires at least one of min or max.');
        }
        parent::__construct([$min, $max]);
    }

    #[\Override]
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
                methodOrClass: self::class,
                operand: $value,
                expected: 'string|array',
            ),
        };

        $min_ok = null === $min ? true : $len >= $min;
        $max_ok = null === $max ? true : $len <= $max;

        if (!$min_ok) {
            if (!$max_ok) {
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: "$type.length_not_in_range",
                    parameters: ['min' => $min, 'max' => $max],
                    methodOrClass: self::class,
                );
            }

            throw GuardException::invalidValue(
                value: $value,
                template_suffix: "$type.length_below_min",
                parameters: ['min' => $min],
                methodOrClass: self::class,
            );
        }

        if (!$max_ok) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: "$type.length_above_max",
                parameters: ['max' => $max],
                methodOrClass: self::class,
            );
        }
    }
}
