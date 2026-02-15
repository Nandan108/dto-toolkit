<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/** @api */
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

        $shortClassName = ProcessingContext::isDevMode()
            // in development, use full class name for error message
            ? $enumClass
            // in production, use short class name to avoid leaking namespaces
            : (new \ReflectionClass($enumClass))->getShortName();

        parent::__construct([$enumClass, $shortClassName]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): \BackedEnum
    {
        [$enumClass, $shortClassName] = $args;

        if (!\is_string($value) && !\is_int($value)) {
            throw TransformException::reason(
                value: $value,
                template_suffix: 'enum.invalid_type',
                parameters: ['enum' => $shortClassName],
                errorCode: 'transform.enum',
            );
        }

        /** @var class-string<\BackedEnum> $enumClass */
        $enumInstance = $enumClass::tryFrom($value);

        if (null === $enumInstance) {
            throw TransformException::reason(
                value: $value,
                template_suffix: 'enum.invalid_value',
                parameters: ['enum' => $shortClassName],
                errorCode: 'transform.enum',
            );
        }

        return $enumInstance;
    }
}
