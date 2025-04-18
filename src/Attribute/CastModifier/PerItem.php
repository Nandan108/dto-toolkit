<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CastModifier;
use Nandan108\DtoToolkit\Core\BaseDto;

/**
 * The PerItem attribute is used to apply the next $count CastTo attributes
 * on each element of the passed array value instead of the whole value.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class PerItem implements CastModifier
{
    public function __construct(
        public readonly int $count = 1,
        public readonly bool $outbound = false
    ) {}

    #[\Override]
    function isOutbound(): bool
    {
        return $this->outbound;
    }

    /**
     * @param \ArrayIterator $queue The queue of attributes to be processed
     * @param \Closure $chain The chain of callables to be executed
     * @param BaseDto $dto The DTO instance
     * @return \Closure A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        // Grab the next $this->count CastTo attributes from the queue into the subchain
        $subAttrs = CastTo::sliceNextAttributes($queue, $this->count);

        // Compose the subchain into a single callable.
        $subchain = CastTo::buildCasterChain($subAttrs, $dto);

        // Into the chain, we now insert our logic, which is to:
        // apply the composed caster ($subchain) on each element of the array value we
        // receive from earlier transformations by $chain().
        return fn(mixed $value): array => array_map($subchain, $chain($value));
    }
}
