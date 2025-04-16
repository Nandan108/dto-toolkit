<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Uppercase extends CastBase implements CasterInterface
{
    #[\Override]
    public function cast(mixed $value, array $args = []): string {
        return strtoupper((string) $value);
    }
}
