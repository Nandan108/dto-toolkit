<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Enum extends CastBase
{
    /** @param class-string<\BackedEnum> $enumClass */
    public function __construct(string $enumClass)
    {
        if (!enum_exists($enumClass)) {
            throw new InvalidArgumentException("Enum caster: '{$enumClass}' is not a valid enum.");
        }

        if (!is_a($enumClass, \BackedEnum::class, true)) {
            throw new InvalidArgumentException("Enum caster: '{$enumClass}' is not a backed enum.");
        }

        parent::__construct([$enumClass]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): \BackedEnum
    {
        [$enumClass] = $args;

        if (!\is_string($value) && !\is_int($value)) {
            throw TransformException::reason(
                methodOrClass: self::class,
                value: $value,
                template_suffix: 'enum.invalid_type',
                parameters: ['enum' => $enumClass],
            );
        }

        /** @var class-string<\BackedEnum> $enumClass */
        $enumInstance = $enumClass::tryFrom($value);

        if (null === $enumInstance) {
            throw TransformException::reason(
                methodOrClass: self::class,
                value: $value,
                template_suffix: 'enum.invalid_value',
                parameters: ['enum' => $enumClass],
            );
        }

        return $enumInstance;
    }
}
