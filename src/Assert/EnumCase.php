<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value matches an enum case.
 *
 * Backed enums accept a case instance or backing value.
 * Unit enums accept a case instance or case name.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class EnumCase extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string<\BackedEnum|\UnitEnum> $class
     *
     * @api
     */
    public function __construct(string $class)
    {
        if (!enum_exists($class)) {
            throw new InvalidArgumentException("EnumCase validator expects an enum class, got {$class}.");
        }
        $isBacked = is_subclass_of($class, \BackedEnum::class);
        parent::__construct([$class, $isBacked]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var class-string<\BackedEnum|\UnitEnum> $enumClass */
        $enumClass = $args[0];
        /** @var bool $isBacked */
        $isBacked = $args[1];
        $enumShortName = (new \ReflectionClass($enumClass))->getShortName();

        if ($value instanceof $enumClass) {
            return;
        }

        if (!$isBacked) {
            if (\is_string($value)) {
                foreach ($enumClass::cases() as $case) {
                    if ($case->name === $value) {
                        return;
                    }
                }
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: 'enum.case',
                    parameters: [
                        'enumClass' => $enumShortName,
                    ],
                );
            } else {
                throw GuardException::expected(
                    operand: $value,
                    expected: 'type.string',
                );
            }
        }

        /** @var class-string<\BackedEnum> $enumClass */
        if (!\is_int($value) && !\is_string($value)) {
            throw GuardException::expected(
                operand: $value,
                expected: ['type.string', 'type.int'],
            );
        }

        if (!$enumClass::tryFrom($value)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'enum.case',
                parameters: [
                    'enumClass' => $enumShortName,
                ],
            );
        }
    }
}
