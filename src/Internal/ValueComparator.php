<?php

declare(strict_types=1);

// @php-cs-fixer-ignore strict_comparison

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * @internal shared comparison helper for CompareTo-based validators
 *
 * @psalm-internal Nandan108\DtoToolkit
 *
 * @phpstan-internal Nandan108\DtoToolkit
 */
final class ValueComparator
{
    public const SUPPORTED_OPERATORS = ['==', '===', '!=', '!==', '<', '<=', '>', '>='];

    public static function compare(
        mixed $left,
        mixed $right,
        string $op,
        bool $leftIsValue,
        bool $rightIsValue,
    ): bool {
        self::assertOperator($op);

        /** @psalm-var mixed */
        $leftNorm = self::normalizeOperand($left, $right, $leftIsValue);
        /** @psalm-var mixed */
        $rightNorm = self::normalizeOperand($right, $left, $rightIsValue);

        if (($leftNorm instanceof \UnitEnum) || ($rightNorm instanceof \UnitEnum)) {
            if (!in_array($op, ['==', '===', '!=', '!=='], true)) {
                throw new InvalidArgumentException("CompareTo validator: operator '{$op}' is not supported for unit enums.");
            }
        }

        return match ($op) {
            '=='  => $leftNorm == $rightNorm,
            '===' => $leftNorm === $rightNorm,
            '!='  => $leftNorm != $rightNorm,
            '!==' => $leftNorm !== $rightNorm,
            '<'   => $leftNorm < $rightNorm,
            '<='  => $leftNorm <= $rightNorm,
            '>'   => $leftNorm > $rightNorm,
            '>='  => $leftNorm >= $rightNorm,
        };
    }

    public static function assertOperator(string $op): void
    {
        if (!in_array($op, self::SUPPORTED_OPERATORS, true)) {
            throw new InvalidArgumentException("CompareTo validator: invalid operator '{$op}'.");
        }
    }

    private static function normalizeOperand(
        mixed $operand,
        mixed $otherOperand,
        bool $operandIsValue,
    ): mixed {
        if ($operand instanceof \BackedEnum) {
            return $operand->value;
        }

        if ($operand instanceof \UnitEnum) {
            return $operand;
        }

        if ($operand instanceof \DateTimeInterface) {
            return $operand->getTimestamp();
        }

        if (is_string($operand) && $otherOperand instanceof \DateTimeInterface) {
            try {
                $parsed = new \DateTimeImmutable($operand);
            } catch (\Exception $e) {
                if ($operandIsValue) {
                    throw GuardException::expected(
                        operand: $operand,
                        expected: 'date_time',
                        parameters: ['type' => 'type.invalid_string'],
                    );
                }

                throw new InvalidArgumentException("CompareTo validator: scalar '{$operand}' is not a valid datetime.", previous: $e);
            }

            return $parsed->getTimestamp();
        }

        return $operand;
    }
}
