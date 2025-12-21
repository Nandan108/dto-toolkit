<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Traits\HasPhase;

abstract class ChainModifierBase implements ProcessingNodeProducerInterface, PhaseAwareInterface
{
    use HasPhase;
}
