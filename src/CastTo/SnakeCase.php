<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\PropAccess\Support\CaseConverter;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SnakeCase extends CastBaseNoArgs
{
    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        return CaseConverter::toSnake($this->ensureStringable($value));
    }
}
