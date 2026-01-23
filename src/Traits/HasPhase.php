<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Enum\Phase;

/**
 * This trait implements PhaseAwareInterface.
 */
trait HasPhase
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
