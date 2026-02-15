<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Converts a numeric value to a rounded float.
 */
/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Rounded extends CastBase
{
    /** @api */
    public function __construct(int $precision = 0)
    {
        parent::__construct([$precision]);
    }

    #[\Override]
    /** @internal */
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
            throw TransformException::expected($value, 'type.numeric');
        }

        return round($floatValue, $precision);
    }
}
