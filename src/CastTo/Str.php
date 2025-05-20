<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Str extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return $this->throwIfNotStringable($value);
    }
}
