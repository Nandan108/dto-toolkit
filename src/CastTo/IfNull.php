<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IfNull extends CastBase implements CasterInterface
{
    public function __construct(mixed $fallback = false, bool $outbound = false)
    {
        parent::__construct($outbound, [$fallback]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): mixed
    {
        return $value ?? $args[0];
    }
}
