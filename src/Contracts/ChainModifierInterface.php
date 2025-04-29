<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

interface ChainModifierInterface extends PhaseAwareInterface
{
    /**
     * Modify the casting chain, given the remaining attribute queue.
     */
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure;
}
