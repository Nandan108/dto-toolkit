<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
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
final class Rounded extends CastBase implements CasterInterface
{
    public function __construct(int $precision = 0, bool $outbound = false)
    {
        parent::__construct($outbound, [$precision]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): ?float
    {
        [$precision] = $args;

        return round((float) $value, $precision);
    }
}
