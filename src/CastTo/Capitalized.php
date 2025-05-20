<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Capitalized extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): ?string
    {
        $value = $this->throwIfNotStringable($value);

        return ucfirst(strtolower($value));
    }
}
