<?php

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class PropGroups implements PhaseAwareInterface
{
    private bool $isOutbound = false;

    public function __construct(
        public array|string $groups,
    ) {}

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        $this->isOutbound = $isOutbound;
    }

    #[\Override]
    public function isOutbound(): bool
    {
        return $this->isOutbound;
    }

    #[\Override]
    public function isIoBound(): bool
    {
        return true;
    }
}
