<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Support\CaseConverter;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SnakeCase extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return CaseConverter::toSnake($this->throwIfNotStringable($value));
    }
}
