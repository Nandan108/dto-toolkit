<?php

namespace Nandan108\DtoToolkit\Contracts;

interface PhaseAwareInterface
{
    public function setOutbound(bool $isOutbound): void;

    public function isOutbound(): bool;

    public function isIoBound(): bool;
}
