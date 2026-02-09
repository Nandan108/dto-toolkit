<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ErrorTemplate extends ChainModifierBase
{
    /**
     * Overrides error message templates for the duration of the subchain.
     *
     * @param non-empty-string|non-empty-array<non-empty-string, non-empty-string> $override a string means an unconditional override, while an array means per-template overrides
     * @param positive-int                                                         $count    number of chain elements to wrap
     */
    public function __construct(
        public readonly array | string $override,
        public readonly int $count = 1,
    ) {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($this->count < 1) {
            throw new InvalidArgumentException('ErrorTemplate count must be greater than 0');
        }
        /** @psalm-suppress TypeDoesNotContainType, DocblockTypeContradiction */
        if (\is_string($override)) {
            '' === $override && throw new InvalidArgumentException('ErrorTemplate override string cannot be empty');
        } else {
            foreach ($override as $key => $value) {
                '' === $key && throw new InvalidArgumentException('ErrorTemplate override keys cannot be empty strings');
                '' === $value && throw new InvalidArgumentException('ErrorTemplate override values cannot be empty strings');
            }
        }
    }

    /**
     * Resolves the effective error template for the given default.
     *
     * @param non-empty-string $default
     *
     * @return non-empty-string
     */
    public static function resolve(string $default): string
    {
        foreach (ProcessingContext::current()->errorTemplateOverrides as $overrides) {
            if (\is_array($overrides)) {
                if (isset($overrides[$default])) {
                    return $overrides[$default];
                }
            } else {
                return $overrides;
            }
        }

        return $default;
    }

    /**
     * @param \ArrayIterator<int, ProcessingNodeProducerInterface> $queue The queue of attributes to be processed
     * @param BaseDto                                              $dto   The DTO instance
     *
     * @return ProcessingChain A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        return new ProcessingChain(
            $queue,
            $dto,
            $this->count,
            'ErrorTemplate',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain): \Closure {
                // Build a subchain made of the next $this->count CastTo attributes from the queue
                $subchain = ProcessingChain::composeFromNodes($chainElements);

                // wrap its execution with override context push/pop
                return function (mixed $value) use ($subchain, $upstreamChain): mixed {
                    $upstreamChain && $value = $upstreamChain($value);

                    $frame = ProcessingContext::current();
                    array_unshift($frame->errorTemplateOverrides, $this->override);
                    try {
                        return $subchain($value);
                    } finally {
                        array_shift($frame->errorTemplateOverrides);
                    }
                };
            },
        );
    }
}
