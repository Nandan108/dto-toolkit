<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

/** @api */
interface ProcessingNodeProducerInterface
{
    /**
     * Creates a ProcessingNode from the given DTO and queue.
     *
     * @param ?\ArrayIterator<int, ProcessingNodeProducerInterface> $queue
     */
    public function getProcessingNode(
        BaseDto $dto,
        ?\ArrayIterator $queue,
    ): ProcessingNodeInterface;
}
