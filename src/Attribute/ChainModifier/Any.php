<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * `Any` is conceptually a “multi-strategy evaluation” modifier.
 * It attempts to apply multiple strategies (casters or validators)
 * in sequence until one of them succeeds.
 *
 * It will return the result of the first successful strategy, which means
 * the unchanged value if the strategy is a validator, or the transformed value
 * if the strategy is a caster.
 *
 * Internally, `Any` will attempt each strategy in order, catching
 * any ProcessingException thrown by individual strategies. If all strategies
 * fail, the modifier throws a single ProcessingException describing the
 * aggregated failure.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Any extends ChainModifierBase
{
    public function __construct(
        public readonly int $count = 1,
    ) {
        $this->count > 1 or throw new InvalidArgumentException('Any: $count must be greater than or equal to 1.');
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
         * @param ProcessingNodeInterface[] $chainElements The elements of the chain
         * @param \Closure|null             $upstreamChain The upstream chain closure
         *
         * @return \Closure A closure that applies the composed caster on each element of the passed array value
         */
        $builder = function (array $chainElements, ?\Closure $upstreamChain): \Closure {
            // get the closure for each node wrapped by Collect
            $closures = array_map(fn (ProcessingNodeInterface $node): \Closure => // foo!
                $node->getBuiltClosure($upstreamChain), $chainElements);

            return function (mixed $value) use ($closures): mixed {
                $failures = [];
                foreach ($closures as $closure) {
                    try {
                        // Try to apply the closure to the value
                        return $closure($value);
                    } catch (ProcessingException $e) {
                        // If it fails, continue to the next closure
                        $failures[] = $e;
                    }
                }

                // If all closures fail, throw!
                throw ProcessingException::reason(
                    methodOrClass: self::class,
                    value: $value,
                    template_suffix: 'modifier.first_success.all_failed',
                    parameters: [
                        'strategy_count' => count($closures),
                    ],
                    errorCode: 'modifier.first_success.all_failed',
                    // messageOverride: "All  nodes wrapped by Any have failed.",
                    debugExtras: ['failures' => $failures],
                );
            };
        };

        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new ProcessingChain(
            queue: $queue,
            dto: $dto,
            count: $this->count,
            className: "Any(count:$this->count)",
            buildCasterClosure: $builder,
        );
    }
}
