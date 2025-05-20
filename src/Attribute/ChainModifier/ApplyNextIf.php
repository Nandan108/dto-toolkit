<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Internal\CasterChain;
use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * The ApplyNextIf chain modifier is used to skip the next $count chain elements.
 * By default, $count is -1, which means it will wrap as many chain elements as possible.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ApplyNextIf extends ChainModifierBase
{
    use UsesParamResolver;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public mixed $condition,
        public int $count = 1,
        public bool $negate = false,
    ) {
    }

    #[\Override]
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue): CasterChain
    {
        $this->configureParamResolver(
            paramName: 'condition',
            valueOrProvider: $this->condition,
            hydrate: fn (mixed $value): bool => (bool) $value,
        );

        return new CasterChain(
            $queue,
            $dto,
            $this->count,
            className: 'ApplyNextIf',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                $subchain = CasterChain::composeFromNodes($chainElements);

                return function (mixed $value) use ($upstreamChain, $subchain): mixed {
                    /** @var bool */
                    $condition = $this->resolveParam('condition', $value, $this->condition);

                    // get the value from upstream
                    if (null !== $upstreamChain) {
                        $value = $upstreamChain($value);
                    }

                    // apply or skip the subchain based on condition ^ negate
                    return ($condition xor $this->negate)
                        ? $subchain($value)
                        : $value;
                };
            }
        );
    }
}
