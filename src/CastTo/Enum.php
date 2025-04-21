<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Enum extends CastBase implements CasterInterface
{
    public function __construct(string $enumClass, bool $outbound = false)
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum caster: '{$enumClass}' is not a valid enum.");
        }

        if (!is_a($enumClass, \BackedEnum::class, true)) {
            throw new \InvalidArgumentException("Enum caster: '{$enumClass}' is not a backed enum.");
        }

        parent::__construct($outbound, [$enumClass]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): \BackedEnum
    {
        /** @var string $enumClass */
        [$enumClass] = $args;

        try {
            /** @psalm-suppress InvalidStringClass */
            return $enumClass::tryFrom($value);
        } catch (\Throwable $t) {
            $value = json_encode($value);
            if (false === $value) {
                $value = '?'.json_last_error_msg().'?';
            }

            throw CastingException::castingFailure(className: self::class, operand: $value, messageOverride: "Invalid enum backing value: $value.");
        }
    }
}
