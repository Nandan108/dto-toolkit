<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ChainModifierInterface;
use Nandan108\DtoToolkit\Traits\HasPhase;

abstract class ChainModifierBase implements ChainModifierInterface
{
    use HasPhase;
}
