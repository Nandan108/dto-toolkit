<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;
use UnitEnum;
use BackedEnum;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Enum extends CastBase implements CasterInterface
{
    public function __construct(
        string $enumClass,
        bool $nullable = false,
        bool $outbound = false,
    ) {
        parent::__construct($outbound, [$enumClass, $nullable]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?UnitEnum
    {
        [$enumClass, $nullable] = $args;

        if ($value === null) {
            if ($nullable) return null;
            throw new CastingException("Enum caster received null, but nullable = false.");
        }
        if (!enum_exists($enumClass)) {
            throw new CastingException("Enum caster: '{$enumClass}' is not a valid enum.");
        }
        if (!is_a($enumClass, BackedEnum::class, true)) {
            throw new CastingException("Enum caster: '{$enumClass}' is not a backed enum.");
        }
        $enumVals = array_map(
            static fn(BackedEnum $case): string|int => $case->value,
            $enumClass::cases()
        );
        if (in_array($value, $enumVals, true)) {
            return $enumClass::from($value);
        }

        throw CastingException::castingFailure(
            className: self::class,
            operand: $value,
            args: $args,
            messageOverride: "Value '{$value}' is invalid for this enum."
        );
    }
}
