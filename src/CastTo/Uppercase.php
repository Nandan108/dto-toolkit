<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Uppercase extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        return strtoupper($this->throwIfNotStringable($value));
    }
}
