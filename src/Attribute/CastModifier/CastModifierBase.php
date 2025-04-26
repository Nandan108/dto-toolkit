<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Traits\HasPhase;

abstract class CastModifierBase implements CastModifierInterface
{
    use HasPhase;
}
