<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * The SkipIfMatch attribute is used to skip the next $count nodes
 * when the current value matches one of the configured values.
 *
 * By default ($count = -1), all remaining nodes in the processing chain
 * are skipped and the input value is returned unchanged.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class SkipIfMatch extends ChainModifierBase
{
    public const RETURN_INPUT = '__dtot_return_input__';

    /**
     * Short-circuit the remaining chain if the current value is found in $matchValues.
     *
     * @param mixed[] $matchValues The values to match against
     * @param int     $count       The number of nodes to skip if a match is found
     *                             Negative values mean "all remaining"
     * @param mixed   $return      The value to return if a match is found.
     *                             Use self::RETURN_INPUT to return the original input value.
     * @param bool    $strict      Whether to use strict comparison when matching values
     * @param bool    $negate      Whether to negate the match (i.e., skip if the value does NOT match)
     */
    public function __construct(
        public array $matchValues,
        public int $count = -1,
        public mixed $return = self::RETURN_INPUT,
        public bool $strict = true,
        public bool $negate = false,
    ) {
    }

    /**
     * @param \ArrayIterator $queue The queue of attributes to be processed
     * @param BaseDto        $dto   The DTO instance
     */
    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        return new ProcessingChain(
            queue: $queue,
            dto: $dto,
            count: $this->count,
            nodeName: 'Mod\SkipIfMatch',
            buildCasterClosure: function (array $chainElements, ?\Closure $upstreamChain): \Closure {
                $subchain = ProcessingChain::composeFromNodes($chainElements);

                return function (mixed $value) use ($upstreamChain, $subchain): mixed {
                    $value = $upstreamChain ? $upstreamChain($value) : $value;
                    $matches = (bool) $this->matchValues
                        && \in_array($value, $this->matchValues, $this->strict);

                    // XOR semantics: skip when match status differs from negate flag
                    if ($matches xor $this->negate) {
                        // Short-circuit: return the $value or the configured value
                        return self::RETURN_INPUT === $this->return
                            ? $value
                            : $this->return;
                    }

                    try {
                        ProcessingContext::pushPropPathNode('Mod\SkipIfMatch');

                        return $subchain($value);
                    } finally {
                        ProcessingContext::popPropPathNode();
                    }
                };
            },
        );
    }
}
