<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Str extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return $this->throwIfNotStringable($value);
    }
}
