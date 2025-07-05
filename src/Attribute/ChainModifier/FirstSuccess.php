<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;

/**
 * The FirstSuccess attribute is used to apply the next $count CastTo attributes
 * on each element of the passed array value instead of the whole value.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FirstSuccess extends ChainModifierBase
{
    public function __construct(
        public readonly int $count = 1,
    ) {
        $this->count > 1 or throw new \InvalidArgumentException('FirstSuccess: $count must be greater than or equal to 1.');
    }

    /**
     * @param \ArrayIterator<int, CasterChainNodeProducerInterface> $queue The queue of attributes to be processed
     * @param BaseDto                                               $dto   The DTO instance
     *
     * @return CasterChain A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue): CasterChain
    {
        /**
         * @param CasterChainNodeInterface[] $chainElements The elements of the chain
         * @param \Closure|null              $upstreamChain The upstream chain closure
         *
         * @return \Closure A closure that applies the composed caster on each element of the passed array value
         */
        $builder = function (array $chainElements, ?\Closure $upstreamChain): \Closure {
            // get the closure for each node wrapped by Collect
            $closures = array_map(fn (CasterChainNodeInterface $node): \Closure => // foo!
                $node->getBuiltClosure($upstreamChain), $chainElements);

            return function (mixed $value) use ($closures): mixed {
                foreach ($closures as $closure) {
                    try {
                        // Try to apply the closure to the value
                        return $closure($value);
                    } catch (CastingException) {
                        // If it fails, continue to the next closure
                    }
                }

                // If all closures fail, throw!
                throw CastingException::castingFailure(className: self::class, operand: $value, messageOverride: "All $this->count nodes wrapped by FirstSuccess have failed.");
            };
        };

        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new CasterChain($queue, $dto, $this->count, "FirstSuccess(count:$this->count)", buildCasterClosure: $builder);
    }
}
