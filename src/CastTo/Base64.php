<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBaseNoArgs;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Base64 extends CastBaseNoArgs
{
    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        return base64_encode($this->ensureStringable($value));
    }
}
