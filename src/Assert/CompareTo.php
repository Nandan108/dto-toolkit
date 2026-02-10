<?php

declare(strict_types=1);

// @php-cs-fixer-ignore strict_comparison

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value compares to a scalar using the given operator.
 *
 * Supported operators: '==', '===', '!=', '!==', '<', '<=', '>', '>='.
 *
 * - If either of the two operands is a DateTimeInterface, an attempt will be made to parse the other operand as a datetime string for comparison. If parsing fails, the behavior depends on which operand is the datetime:
 *   - If the datetime is the operand being validated (left operand), a validation failure will be raised.
 *   - If the datetime is the scalar argument (right operand), an InvalidArgumentException will be thrown, as this indicates a misconfiguration of the validator.
 * - If either operand is a BackedEnum, the comparison will be made against the backing value of the enum case.
 * - If either operand is a UnitEnum, the comparison will be made against the enum case itself, and only '==', '===', '!=', and '!==' operators are supported.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CompareTo extends ValidatorBase
{
    /**
     * @param '=='|'==='|'!='|'!=='|'<'|'<='|'>'|'>=' $op
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(string $op, mixed $scalar)
    {
        parent::__construct([$op, $scalar]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$op, $scalar] = $args;

        $matches = self::compareValues(
            left: $value,
            right: $scalar,
            op: $op,
            leftIsValue: true,
            rightIsValue: false,
        );

        if (!$matches) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'compare_to',
                errorCode: 'guard.compare_to',
                parameters: [
                    'operator' => $op,
                    'left'     => $value,
                    'right'    => $scalar,
                ],
            );
        }
    }

    public static function compareValues(
        mixed $left,
        mixed $right,
        string $op,
        bool $leftIsValue,
        bool $rightIsValue,
    ): bool {
        self::assertOperator($op);

        $leftNorm = self::normalizeOperand($left, $right, $leftIsValue);
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

    private static function assertOperator(string $op): void
    {
        $allowed = ['==', '===', '!=', '!==', '<', '<=', '>', '>='];
        if (!in_array($op, $allowed, true)) {
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
