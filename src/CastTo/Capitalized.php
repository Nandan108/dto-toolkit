<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Capitalized extends CastBase implements CasterInterface
{
    #[\Override]
    public function cast(mixed $value, array $args = []): ?string
    {
        $value = $this->throwIfNotStringable($value);

        return ucfirst(strtolower($value));
    }
}
