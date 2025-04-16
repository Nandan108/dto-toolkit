<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * Converts a value to bool.
 *
 * In case of casting failure, where $value can't be converted to bool:
 * - if $nullable = true, will return null.
 * - if $nullable = false, will throw a CastingException.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Boolean extends CastBase implements CasterInterface
{
    public function __construct(bool $nullable = true, bool $strict = false, bool $outbound = false)
    {
        parent::__construct($outbound, [$nullable, $strict]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?bool
    {
        [$nullable, $strict] = $args;

        // bool is returned as-is
        if (is_bool($value)) return $value;

        // strings, we convert to bool: ("1", "yes", "on") => true, ("0", "no", "off") => false, etc...
        if (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_bool($value)) return $value;
        }

        // If we're not allowed to return null
        if (!$nullable) {
            // throw an error
            if ($strict) {
                throw CastingException::castingFailure(
                    className: self::class,
                    operand: $value,
                    args: $args,
                );
            }
            // convert to bool according to trueish/falseish
            return (bool)$value;
        }
        // we can return null, so let's do that
        return null;
    }
}
