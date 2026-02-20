<?php

declare(strict_types=1);

// @php-cs-fixer-ignore strict_comparison

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Internal\ValueComparator;

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
     *
     * @api
     */
    public function __construct(string $op, mixed $scalar)
    {
        ValueComparator::assertOperator($op);

        parent::__construct([$op, $scalar]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @psalm-suppress UnnecessaryVarAnnotation, MixedAssignment */
        /** @var array{0: non-empty-string, 1: mixed} $args */
        [$op, $scalar] = $args;

        $matches = ValueComparator::compare(
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
}
