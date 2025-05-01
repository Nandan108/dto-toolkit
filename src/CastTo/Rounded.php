<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * Converts a value to bool.
 *
 * In case of casting failure, where $value can't be converted to bool:
 * - if $nullable = true, will return null.
 * - if $nullable = false, will throw a CastingException.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Rounded extends CastBase
{
    public function __construct(int $precision = 0)
    {
        parent::__construct([$precision]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        [$precision] = $args;

        if (is_numeric($value)) {
            $floatValue = (float) $value;
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $string = (string) $value;
            if (is_numeric($string)) {
                $floatValue = (float) $string;
            }
        }

        if (!isset($floatValue)) {
            throw CastingException::castingFailure(className: $this::class, operand: $value, messageOverride: 'Expected numeric, but got '.gettype($value));
        }

        return round($floatValue, $precision);
    }
}
