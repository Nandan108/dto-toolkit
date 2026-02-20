<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * #[Mod\Assert($count)] fan-out modifier:
 * - runs the next $count nodes; each receives the same upstream value
 * - does not pipe one node's output into the next
 * - lets exceptions bubble; all nodes must succeed
 * - returns the original value (assert-only)
 *
 * Use when you need independent validations on the same input
 * without altering its value.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Assert extends ChainModifierBase
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly int $count = 1,
    ) {
        /** @psalm-suppress DocblockTypeContradiction */
        $this->count > 0 || throw new InvalidArgumentException('Assert: $count must be greater than or equal to 1.');
    }

    /**
     * @param \ArrayIterator<int, ProcessingNodeProducerInterface> $queue The queue of attributes to be processed
     * @param BaseDto                                              $dto   The DTO instance
     *
     * @return ProcessingChain A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        /**
         * @param array<ProcessingNodeInterface> $chainElements The elements of the chain
         * @param \Closure|null                  $upstreamChain The upstream chain closure
         *
         * @return \Closure A closure that applies the composed caster on each element of the passed array value
         */
        $builder = function (array $chainElements, ?\Closure $upstreamChain): \Closure {
            // get the closure for each node wrapped by Assert
            $closures = [];
            /** @var ProcessingNodeInterface $node */
            foreach ($chainElements as $node) {
                $closures[] = $node->getBuiltClosure(null);
            }

            return function (mixed $value) use ($closures, $upstreamChain): mixed {
                /** @psalm-var mixed */
                $upstreamValue = $upstreamChain ? $upstreamChain($value) : $value;

                ProcessingContext::pushPropPathNode('Mod\Assert');
                try {
                    foreach ($closures as $k => $closure) {
                        ProcessingContext::pushPropPath($k);
                        try {
                            $closure($upstreamValue);
                        } finally {
                            ProcessingContext::popPropPath();
                        }
                    }
                } finally {
                    ProcessingContext::popPropPathNode();
                }

                return $upstreamValue;
            };
        };

        return new ProcessingChain(
            queue: $queue,
            dto: $dto,
            count: $this->count,
            nodeName: "Mod\Assert(count:$this->count)",
            buildCasterClosure: $builder,
        );
    }
}
