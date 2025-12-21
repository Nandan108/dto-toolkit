<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class EnumBackedValue extends ValidateBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string<\BackedEnum> $enumClass
     **/
    public function __construct(string $enumClass)
    {
        if (!is_subclass_of($enumClass, \BackedEnum::class)) {
            throw new InvalidConfigException("EnumBackedValue validator expects a BackedEnum class, got {$enumClass}.");
        }

        parent::__construct([$enumClass]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        /** @var class-string<\BackedEnum> */
        $enumClass = $args[0];

        if ($value instanceof $enumClass) {
            throw GuardException::failed("must be a backing value of {$enumClass}, instance given");
        }

        if ($enumClass::tryFrom($value) instanceof $enumClass) {
            return;
        }

        throw GuardException::failed("must be a backing value of {$enumClass}");
    }
}
