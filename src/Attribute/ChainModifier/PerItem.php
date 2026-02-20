<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * The PerItem attribute is used to apply the next $count CastTo attributes
 * on each element of the passed array value instead of the whole value.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class PerItem extends ChainModifierBase
{
    public function __construct(public readonly int $count = 1)
    {
    }

    /**
     * @param BaseDto                                               $dto   The DTO instance
     * @param ?\ArrayIterator<int, ProcessingNodeProducerInterface> $queue The queue of attributes to be processed
     *
     * @return ProcessingChain A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new ProcessingChain(
            $queue,
            $dto,
            $this->count,
            'Mod\PerItem',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                $subchain = ProcessingChain::composeFromNodes($chainElements);

                // Into the chain, we now insert our logic, which is to:
                // apply the composed caster ($subchain) on each element of the array value we
                // receive from earlier transformations by $chain().
                return function (mixed $value) use ($upstreamChain, $subchain): array {
                    /** @psalm-var mixed $value */
                    $value = $upstreamChain ? $upstreamChain($value) : $value;

                    ProcessingContext::pushPropPathNode('Mod\PerItem');

                    // If the value is not an array, we throw!
                    if (!is_array($value)) {
                        throw ProcessingException::reason(
                            value: $value,
                            template_suffix: 'modifier.per_item.expected_array',
                            errorCode: 'modifier.per_item.expected_array',
                        );
                    }
                    $result = [];
                    try {
                        /** @psalm-var mixed $v */
                        foreach ($value as $k => $v) {
                            ProcessingContext::pushPropPath($k);
                            try {
                                /** @psalm-var mixed */
                                $result[$k] = $subchain($v);
                            } finally {
                                ProcessingContext::popPropPath();
                            }
                        }
                    } finally {
                        ProcessingContext::popPropPathNode();
                    }

                    return $result;
                };
            },
        );
    }
}
