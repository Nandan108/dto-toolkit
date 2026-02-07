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
 * The Collect attribute is used to run input through multiple parallel subchains and collects their outputs.
 * If keys are provided, returns an associative array.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Collect extends ChainModifierBase
{
    /** @var array<non-empty-string> */
    private array $keys;

    /**
     * @param array<non-empty-string>|int $countOrKeys
     */
    public function __construct(
        public array | int $countOrKeys,
    ) {
        /** @psalm-suppress InvalidReturnStatement, NoValue */
        $throw = fn (string $error): mixed => throw new InvalidArgumentException(self::class.": $error");

        if (\is_array($countOrKeys)) {
            $keys = $this->keys = $countOrKeys;
            foreach ($keys as $key) {
                /** @psalm-suppress DocblockTypeContradiction */
                \is_string($key) || $throw('The keys must be strings');
            }
            \count($keys) === \count(array_unique($keys)) || $throw('The keys must be unique');
        } else {
            $countOrKeys >= 1 || $throw('If a count is given, it must be greater than 0');
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->keys = range(0, $countOrKeys - 1);
        }
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

        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new ProcessingChain(
            $queue,
            $dto,
            \count($this->keys),
            'Mod\Collect',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                // get the closure for each node wrapped by Collect
                $closures = array_map(fn ($node): callable => $node->getBuiltClosure($upstreamChain), $chainElements);

                return function (mixed $value) use ($closures): array {
                    $result = [];
                    ProcessingContext::pushPropPathNode('Mod\Collect');

                    try {
                        foreach ($closures as $i => $closure) {
                            ProcessingContext::pushPropPath($this->keys[$i]);

                            try {
                                /** @psalm-var mixed */
                                $result[$this->keys[$i]] = $closure($value);
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
