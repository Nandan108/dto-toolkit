<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Enum\Phase;

interface PhaseAwareInterface
{
    public function setOutbound(bool $isOutbound): void;

    public function getPhase(): Phase;
}
