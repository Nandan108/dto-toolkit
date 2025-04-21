<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ReplaceIf extends CastBase implements CasterInterface
{
    public function __construct(mixed $when, mixed $then = null, bool $strict = true, bool $outbound = false)
    {
        parent::__construct($outbound, [$when, $then, $strict]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): mixed
    {
        [$when, $then, $strict] = $args;

        $match = is_array($when)
            ? in_array($value, $when, $strict)
            : ($strict ? $value === $when : $value == $when);

        return $match ? $then : $value;
    }
}
