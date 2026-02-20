<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;
use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * The FailIf chain modifier is used to fail immediately if $condition is met.
 * By default, $count is -1, which means it will wrap as many chain elements as possible.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailIf extends ChainModifierBase
{
    use UsesParamResolver;

    public function __construct(
        public readonly string $condition,
        public readonly bool $negate = false,
    ) {
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
            queue: $queue,
            dto: $dto,
            count: 0,
            nodeName: 'Mod\FailIf',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                return function (mixed $value) use ($upstreamChain): mixed {
                    /** @var bool */
                    $condition = $this->resolveParam('condition', $value, $this->condition);

                    /** @psalm-var mixed $value */
                    $value = $upstreamChain ? $upstreamChain($value) : $value;

                    if ($condition xor $this->negate) {
                        ProcessingContext::pushPropPathNode('Mod\FailIf');

                        throw ProcessingException::reason(
                            value: $value,
                            template_suffix: 'modifier.fail_if.condition_failed',
                            parameters: [
                                'condition' => json_encode($this->condition),
                                'negated'   => $this->negate,
                            ],
                        );
                    }

                    // apply casting if condition not met
                    return $value;
                };
            },
        );
    }
}
