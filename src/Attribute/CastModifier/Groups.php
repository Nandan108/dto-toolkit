<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Support\CasterChainBuilder;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Groups extends CastModifierBase
{
    public function __construct(
        public array|string $groups,
        public int $count = 1,
    ) {
    }

    #[\Override]
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        if (!$dto instanceof HasGroupsInterface) {
            throw new \LogicException('DTO must use UsesGroups trait for Groups modifier to work.');
        }

        // consume the subchain, whether it'll be applied or not
        $subchain = CasterChainBuilder::buildNextSubchain(
            length: $this->count,
            queue: $queue,
            dto: $dto,
            modifier: 'Groups'
        );

        $phase = Phase::fromComponents($this->isOutbound(), false);
        $groups = is_array($this->groups) ? $this->groups : [$this->groups];

        // apply the subchain only if the groups are in scope
        if ($dto->groupsAreInScope($phase, $groups)) {
            return fn (mixed $value): mixed => $subchain($chain($value));
        }

        // Just skip — passthrough unchanged
        return $chain;
    }
}
