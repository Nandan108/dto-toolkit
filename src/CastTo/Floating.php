<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Floating extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args = []): ?float
    {
        return (float) $value;
    }
}
