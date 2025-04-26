<?php

namespace Nandan108\DtoToolkit\Traits;

trait HasPhase // implments PhaseAwareInterface
{
    protected $ioBound = false;
    protected $outbound = false;

    // PhaseAwareInterface methods

    #[\Override]
    public function isIoBound(): bool
    {
        return $this->ioBound;
    }

    #[\Override]
    public function isOutbound(): bool
    {
        return $this->outbound;
    }

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        $this->outbound = $isOutbound;
    }
}
