<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

interface CasterChainNodeProducerInterface
{
    /**
     * Creates a CasterChainNode from the given DTO and queue.
     *
     * @param ?\ArrayIterator<int, CasterChainNodeProducerInterface> $queue
     */
    public function getCasterChainNode(
        BaseDto $dto,
        ?\ArrayIterator $queue,
    ): CasterChainNodeInterface;
}
