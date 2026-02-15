<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Lowercase extends CastBaseNoArgs
{
    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        return strtolower($this->ensureStringable($value));
    }
}
