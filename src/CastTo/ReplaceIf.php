<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ReplaceIf extends CastBase implements CasterInterface
{
    public function __construct(mixed $when, mixed $then = null, bool $outbound = false)
    {
        parent::__construct($outbound, [$when, $then]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): mixed
    {
        [$when, $then] = $args;

        $match = is_array($when)
            ? in_array($value, $when, true)
            : $value === $when;

        return $match ? $then : $value;
    }
}
