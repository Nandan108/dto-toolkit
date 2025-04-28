<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Enum\Phase;

trait HasPhase // implements PhaseAwareInterface
{
    protected bool $isIoBound = false;
    protected bool $isOutbound = false;

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        $this->isOutbound = $isOutbound;
    }

    #[\Override]
    public function getPhase(): Phase
    {
        return Phase::fromComponents($this->isOutbound, $this->isIoBound);
    }
}
