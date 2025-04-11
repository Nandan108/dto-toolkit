<?php

namespace Nandan108\DtoToolkit\Core;

use Attribute;
use Nandan108\DtoToolkit\Attribute\CastTo as BaseCastTo;
use Nandan108\DtoToolkit\Traits\CanCastBasicValues;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CastTo extends BaseCastTo
{
    use CanCastBasicValues;
}