<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ReplaceIf extends CastBase
{
    public function __construct(mixed $when, mixed $then = null, bool $strict = true)
    {
        parent::__construct([$when, $then, $strict]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        [$when, $then, $strict] = $args;

        $match = in_array($value, (array) $when, $strict);

        return $match ? $then : $value;
    }
}
