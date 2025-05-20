<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Enum\Phase;

trait HasPhase // implements PhaseAwareInterface
{
    protected bool $isIoBound = false;
    protected bool $isOutbound = false;

    /** @psalm-suppress PossiblyUnusedMethod, InvalidOverride */
    #[\Override]
    public function setOutbound(bool $isOutbound = true): void
    {
        $this->isOutbound = $isOutbound;
    }

    /** @psalm-suppress InvalidOverride */
    #[\Override]
    public function getPhase(): Phase
    {
        return Phase::fromComponents($this->isOutbound, $this->isIoBound);
    }
}
