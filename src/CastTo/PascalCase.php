<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\PropAccess\Support\CaseConverter;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class PascalCase extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return CaseConverter::toPascal($this->throwIfNotStringable($value));
    }
}
