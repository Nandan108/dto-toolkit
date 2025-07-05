<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;

/**
 * The Collect attribute is used to.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Collect extends ChainModifierBase
{
    /** @var array<string> */
    private array $keys;

    /**
     * @param array<string>|int $countOrKeys
     */
    public function __construct(
        public array|int $countOrKeys,
    ) {
        /** @psalm-suppress InvalidReturnStatement, NoValue */
        $throw = fn (string $error): mixed => throw CastingException::castingFailure(className: self::class, operand: $countOrKeys, messageOverride: "Mod\Collect: $error");

        if (is_array($countOrKeys)) {
            $keys = $this->keys = $countOrKeys;
            foreach ($keys as $key) {
                /** @psalm-suppress DocblockTypeContradiction */
                is_string($key) or $throw('The keys must be strings');
            }
            count($keys) === count(array_unique($keys)) or $throw('The keys must be unique');
        } else {
            $countOrKeys >= 1 or $throw('If a count is given, it must be greater than 0');
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->keys = range(0, $countOrKeys - 1);
        }
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

        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        return new CasterChain($queue, $dto, count($this->keys), 'Collect',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                // get the closure for each node wrapped by Collect
                $closures = array_map(fn ($node): callable => $node->getBuiltClosure($upstreamChain), $chainElements);

                return function (mixed $value) use ($closures): array {
                    $result = [];

                    foreach ($closures as $i => $closure) {
                        CastTo::pushPropPath($this->keys[$i]);

                        try {
                            /** @psalm-var mixed */
                            $result[$this->keys[$i]] = $closure($value);
                        } finally {
                            CastTo::popPropPath();
                        }
                    }

                    return $result;
                };
            }
        );
    }
}
