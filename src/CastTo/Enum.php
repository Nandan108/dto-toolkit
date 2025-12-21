<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Enum extends CastBase
{
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
        /** @var string $enumClass */
        [$enumClass] = $args;

        try {
            /** @psalm-suppress InvalidStringClass */
            return $enumClass::tryFrom($value);
        } catch (\Throwable $t) {
            throw TransformException::reason(
                methodOrClass: self::class,
                value: $value,
                template_suffix: 'enum.unable_to_cast',
                parameters: [
                    'enum'    => $enumClass,
                    'message' => $t->getMessage(),
                ],
            );
        }
    }
}
