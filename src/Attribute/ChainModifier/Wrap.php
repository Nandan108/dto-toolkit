<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * The Wrap attribute is used only to wrap zero or more nodes in the chain.
 * It applies no chain transformation beyond simple composition of the subchain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Wrap extends ChainModifierBase
{
    /**
     * If $count < 0, it will wrap as many elements as are available in the queue.
     * If $count is 0, then this element is a no-op.
     * If $count > 0, it will wrap precisely the next $count elements, and throw if less than $count are available.
     */
    public function __construct(public readonly int $count = 1)
    {
    }

    /**
     * @param \ArrayIterator $queue The queue of attributes to be processed
     * @param BaseDto        $dto   The DTO instance
     *
     * @return ProcessingChain Just wraps the next $this->count CastTo attributes
     */
    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new ProcessingChain($queue, $dto, $this->count, "Wrap($this->count)");
    }
}
