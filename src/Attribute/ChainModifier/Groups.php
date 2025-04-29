<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Support\CasterChainBuilder;

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
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        if (!$dto instanceof HasGroupsInterface) {
            throw new \LogicException('To use #[Groups], DTO must use UsesGroups trait or implement HasGroupsInterface');
        }

        // consume the subchain, whether it'll be applied or not
        $subchain = CasterChainBuilder::buildNextSubchain(
            length: $this->count,
            queue: $queue,
            dto: $dto,
            modifier: 'Groups'
        );

        // apply the subchain only if the groups are in scope
        if ($dto->groupsAreInScope($this->getPhase(), (array) $this->groups)) {
            return fn (mixed $value): mixed => $subchain($chain($value));
        }

        // Just skip — passthrough unchanged
        return $chain;
    }
}
