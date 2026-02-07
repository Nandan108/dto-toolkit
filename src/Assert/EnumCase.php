<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value matches an enum case (by name or instance).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class EnumCase extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string<\BackedEnum> $enumClass
     **/
    public function __construct(string $enumClass)
    {
        if (!enum_exists($enumClass)) {
            throw new InvalidConfigException("EnumCase validator expects an enum class, got {$enumClass}.");
        }
        parent::__construct([$enumClass]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $enumClass = $args[0];

        if ($value instanceof $enumClass) {
            return;
        }

        if (method_exists($enumClass, 'tryFrom')) {
            $case = $enumClass::tryFrom($value);
            if ($case instanceof $enumClass) {
                return;
            }
        }

        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'enum.invalid_case',
            parameters: ['enum' => $enumClass],
            errorCode: 'validate.enum.invalid_case',
        );
    }
}
