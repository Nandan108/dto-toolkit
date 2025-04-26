<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Floating extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $string = (string) $value;
            if (is_numeric($string)) {
                return (float) $string;
            }
        }
        throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: 'Expected numeric, but got '.gettype($value));
    }
}
