<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Boolean extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args): ?bool
    {
        // bool is returned as-is
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (bool) $value;
        }

        // strings, we convert to bool: ("1", "yes", "on") => true, ("0", "no", "off") => false, etc...
        if (is_string($value)) {
            $filteredVal = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_bool($filteredVal)) {
                return $filteredVal;
            }
        }

        throw CastingException::castingFailure(className: $this::class, operand: $value);
    }
}
