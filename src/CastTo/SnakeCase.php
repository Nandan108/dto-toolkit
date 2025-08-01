<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\PropAccess\Support\CaseConverter;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SnakeCase extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return CaseConverter::toSnake($this->throwIfNotStringable($value));
    }
}
