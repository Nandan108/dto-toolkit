<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;
use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * The ApplyNextIf chain modifier is used to skip the next $count chain elements.
 * By default, $count is -1, which means it will wrap as many chain elements as possible.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailIf extends ChainModifierBase
{
    use UsesParamResolver;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(
        public readonly mixed $condition,
        public readonly bool $negate = false,
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

        return new CasterChain(queue: $queue, dto: $dto, count: 0, className: 'FailIf',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                return function (mixed $value) use ($upstreamChain): mixed {
                    /** @var bool */
                    $condition = $this->resolveParam('condition', $value, $this->condition);

                    if ($condition xor $this->negate) {
                        throw CastingException::castingFailure(static::class, $value, messageOverride: 'Condition failed');
                    }

                    // apply casting
                    return null === $upstreamChain ? $value : $upstreamChain($value);
                };
            }
        );
    }
}
