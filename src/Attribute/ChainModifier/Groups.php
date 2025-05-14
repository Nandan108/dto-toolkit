<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

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
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue): CasterChain
    {
        if (!$dto instanceof HasGroupsInterface) {
            throw new \LogicException('To use #[Groups], DTO must use UsesGroups trait or implement HasGroupsInterface');
        }

        // consume the subchain, whether it'll be applied or not, decide application at cast-time
        return new CasterChain($queue, $dto, $this->count, 'PerItem',
            buildCasterClosure: function (array $chainElements, ?\Closure $upstreamChain) use ($dto): callable {
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
