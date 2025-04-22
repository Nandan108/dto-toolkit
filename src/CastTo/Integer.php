<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\IntCastMode;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Integer extends CastBase implements CasterInterface
{
    public function __construct(IntCastMode $mode = IntCastMode::Trunc)
    {
        parent::__construct(args: [$mode]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): int
    {
        /** @var IntCastMode $mode */
        $mode = $args[0];

        if (is_int($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            $floatVal = (float) $value;
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $string = (string) $value;
            if (is_numeric($string)) {
                $floatVal = (float) $string;
            }
        }

        if (isset($floatVal)) {
            return match ($mode) {
                IntCastMode::Trunc => (int) $floatVal,
                IntCastMode::Floor => (int) floor($floatVal),
                IntCastMode::Ceil  => (int) ceil($floatVal),
                IntCastMode::Round => (int) round($floatVal),
            };
        }

        throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: 'Expected numeric, but got '.gettype($value));
    }
}
