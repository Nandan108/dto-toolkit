<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Str extends CastBase implements CasterInterface
{
    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        return $this->throwIfNotStringable($value);
    }
}
