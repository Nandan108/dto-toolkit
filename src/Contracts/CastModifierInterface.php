<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

interface CastModifierInterface
{
    /**
     * @return bool true if the modifier applies to outbound casting, false otherwise
     */
    public function isOutbound(): bool;

    /**
     * Modify the casting chain, given the remaining attribute queue.
     */
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure;
}
