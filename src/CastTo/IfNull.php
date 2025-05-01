<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IfNull extends CastBase
{
    public function __construct(mixed $fallback = false)
    {
        parent::__construct([$fallback]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        return $value ?? $args[0];
    }
}
