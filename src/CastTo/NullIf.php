<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class NullIf extends ReplaceIf implements CasterInterface
{
    public function __construct(mixed $when, bool $strict = true, bool $outbound = false)
    {
        parent::__construct($when, null, $strict, $outbound);
    }
}
