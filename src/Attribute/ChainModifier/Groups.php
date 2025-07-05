<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Internal\CasterChain;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Groups extends ChainModifierBase
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public function __construct(
        public array|string $groups,
        public int $count = 1,
    ) {
    }

    #[\Override]
    /**
     * Summary of getCasterChainNode.
     *
     * @param ?\ArrayIterator<int, CasterChainNodeProducerInterface> $queue
     *
     * @throws \LogicException
     */
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue): CasterChain
    {
        if (!$dto instanceof HasGroupsInterface) {
            throw new \LogicException('To use #[Groups], DTO must use UsesGroups trait or implement HasGroupsInterface');
        }

        /*
        xpects       callable(array<array-key, Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface>, callable|null):Closure|null,
        but    impure-Closure(array<array-key, Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface>, callable|null):(callable|pure-Closure(mixed):mixed) provided
        */
        // consume the subchain, whether it'll be applied or not, decide application at cast-time
        return new CasterChain($queue, $dto, $this->count, 'PerItem',
            buildCasterClosure:
            /**
             * @param CasterChainNodeInterface[] $chainElements
             */
            function (array $chainElements, ?\Closure $upstreamChain) use ($dto): callable {
                // apply the subchain only if the groups are in scope
                if ($this->inPhase($dto)) {
                    return CasterChain::composeFromNodes($chainElements, $upstreamChain);
                }

                // Just skip — passthrough unchanged
                return $upstreamChain ?? fn (mixed $value): mixed => $value;
            }
        );
    }

    protected function inPhase(HasGroupsInterface $dto): bool
    {
        return $dto->groupsAreInScope($this->getPhase(), (array) $this->groups);
    }
}
