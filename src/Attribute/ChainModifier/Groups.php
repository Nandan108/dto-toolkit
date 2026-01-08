<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Groups extends ChainModifierBase
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public function __construct(
        public array | string $groups,
        public int $count = 1,
    ) {
    }

    #[\Override]
    /**
     * Summary of getProcessingNode.
     *
     * @param ?\ArrayIterator<int, ProcessingNodeProducerInterface> $queue
     *
     * @throws InvalidConfigException
     */
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        if (!$dto instanceof HasGroupsInterface) {
            throw new InvalidConfigException('To use #[Groups], DTO must use UsesGroups trait or implement HasGroupsInterface');
        }

        // consume the subchain, whether it'll be applied or not, decide application at cast-time
        return new ProcessingChain(
            $queue,
            $dto,
            $this->count,
            'PerItem',
            buildCasterClosure:
            /**
             * @param ProcessingNodeInterface[] $chainElements
             */
            function (array $chainElements, ?\Closure $upstreamChain) use ($dto): callable {
                // apply the subchain only if the groups are in scope
                if ($this->inPhase($dto)) {
                    return ProcessingChain::composeFromNodes($chainElements, $upstreamChain);
                }

                // Just skip — passthrough unchanged
                return $upstreamChain ?? fn (mixed $value): mixed => $value;
            },
        );
    }

    protected function inPhase(HasGroupsInterface $dto): bool
    {
        return $dto->groupsAreInScope($this->getPhase(), (array) $this->groups);
    }
}
