<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a backing value of a BackedEnum class.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class EnumBackedValue extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string<\BackedEnum> $enumClass
     **/
    /** @api */
    public function __construct(string $enumClass)
    {
        if (!is_subclass_of($enumClass, \BackedEnum::class)) {
            throw new InvalidArgumentException("EnumBackedValue validator expects a BackedEnum class, got {$enumClass}.");
        }

        parent::__construct([$enumClass]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var class-string<\BackedEnum> */
        $enumClass = $args[0];

        if ($value instanceof $enumClass) {
            throw GuardException::expected(
                operand: $value,
                templateSuffix: 'backing_value.instance_given',
                expected: 'type.enum.backing_value',
                parameters: [
                    'enumClass' => (new \ReflectionClass($enumClass))->getShortName(),
                ],
                debug: ['fullEnumClass' => $enumClass],
            );
        }

        try {
            // Use tryFrom to check validity without throwing an exception
            if ($enumClass::tryFrom($value) instanceof $enumClass) {
                return;
            }

        } catch (\TypeError $e) {
            // If a TypeError is thrown, it means the value is of an incompatible type (e.g., object or array)
            throw GuardException::expected(
                operand: $value,
                templateSuffix: 'backing_value.invalid_type',
                expected: 'type.enum.backing_value',
                parameters: [
                    'enumClass' => (new \ReflectionClass($enumClass))->getShortName(),
                ],
            );
        }

        throw GuardException::expected(
            operand: $value,
            expected: 'type.enum.backing_value',
            parameters: [
                'enumClass' => (new \ReflectionClass($enumClass))->getShortName(),
                'allowed'   => array_map(
                    fn ($case) => (string) $case->value,
                    $enumClass::cases(),
                ),
            ],
            debug: ['fullEnumClass' => $enumClass],
        );
    }
}
