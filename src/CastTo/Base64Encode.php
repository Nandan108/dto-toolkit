<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Base64Encode extends CastBase
{
    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        return base64_encode($this->throwIfNotStringable($value));
    }
}
