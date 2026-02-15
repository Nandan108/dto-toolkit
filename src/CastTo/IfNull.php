<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IfNull extends CastBase
{
    /** @api */
    public function __construct(mixed $fallback = false)
    {
        parent::__construct([$fallback]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        return $value ?? $args[0];
    }
}
