<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;
use UnitEnum;
use BackedEnum;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Enum extends CastBase implements CasterInterface
{
    public function __construct(string $enumClass, bool $outbound = false)
    {
        parent::__construct($outbound, [$enumClass]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): BackedEnum
    {
        $enumClass = (string)($args[0] ?? '');

        $throw = static function (?string $messageOverride=null) use ($value): never {
            if ($messageOverride === null) {
                try {
                    $value = json_encode($value, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $value = '?'.$e->getMessage().'?';
                }
                $messageOverride = "Invalid enum backing value: $value.";
            }
            throw CastingException::castingFailure(
                className: self::class,
                operand: $value,
                messageOverride: $messageOverride,
            );
        };

        if (!enum_exists($enumClass)) {
            $throw("Enum caster: '{$enumClass}' is not a valid enum.");
        }

        if (!is_a($enumClass, BackedEnum::class, true)) {
            $throw("Enum caster: '{$enumClass}' is not a backed enum.");
        }

        try {
            return $enumClass::tryFrom($value);
        } catch (\Throwable $t) {
            $throw();
        }
    }
}
