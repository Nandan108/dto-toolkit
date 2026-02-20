<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Internal\ProcessingChain;
use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * The ApplyNextIf chain modifier is used to apply the next $count chain elements conditionally.
 * By default, $count is -1, which means it will wrap as many chain elements as possible.
 *
 * $condition is resolved at processing time, via the UsesParamResolver trait.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ApplyNextIf extends ChainModifierBase
{
    use UsesParamResolver;

    /** @var non-empty-string */
    protected static string $name = 'Mod\ApplyNextIf';

    public function __construct(
        public string $condition,
        public int $count = 1,
        public bool $negate = false,
    ) {
        /** @psalm-suppress DocblockTypeContradiction */
        $this->count || throw new \InvalidArgumentException(static::$name.': $count cannot be zero.');
    }

    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        $this->configureParamResolver(
            paramName: 'condition',
            valueOrProvider: $this->condition,
            hydrate: fn (mixed $value): bool => (bool) $value,
        );

        return new ProcessingChain(
            $queue,
            $dto,
            $this->count,
            nodeName: static::$name,
            /** @param array<array-key, ProcessingNodeInterface> $chainElements */
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                $subchain = ProcessingChain::composeFromNodes($chainElements);

                return function (mixed $value) use ($upstreamChain, $subchain): mixed {
                    /** @var bool */
                    $condition = $this->resolveParam('condition', $value, $this->condition);

                    // get the value from upstream
                    /** @psalm-var mixed $value */
                    $value = $upstreamChain ? $upstreamChain($value) : $value;

                    // apply or skip the subchain based on condition ^ negate
                    if ($condition xor $this->negate) {
                        ProcessingContext::pushPropPathNode(static::$name);
                        try {
                            return $subchain($value);
                        } finally {
                            ProcessingContext::popPropPathNode();
                        }
                    }

                    return $value;
                };
            },
        );
    }
}
