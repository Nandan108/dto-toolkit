<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Traits\HasPhase;

abstract class ChainModifierBase implements CasterChainNodeProducerInterface, PhaseAwareInterface
{
    use HasPhase;
}
