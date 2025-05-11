<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Internal\CasterChain;

interface ChainModifierInterface extends PhaseAwareInterface
{
    /**
     * Returns a closure that takes a $chain amd a $subChain and returns a modified chain.
     */
    public function getModifier(\ArrayIterator $queue, BaseDto $dto): CasterChain;
}
