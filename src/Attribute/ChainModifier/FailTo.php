<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * The FailTo attribute is used to catch and handle exceptions
 * thrown by any caster declared earlier in the chain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailTo extends ChainModifierBase
{
    protected \Closure | array | null $_handler = null;

    /**
     * The FailTo attribute is used to catch and handle exceptions
     * thrown by any caster declared earlier in the chain.
     */
    public function __construct(
        public readonly mixed $fallback = null,
        public readonly string | array | null $handler = null,
    ) {
    }

    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        $handler = $this->resolveHandler($dto);

        return new ProcessingChain(
            queue: $queue,
            dto: $dto,
            count: 0,
            nodeName: 'Mod\FailTo',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain) use ($handler, $dto): \Closure {
                if (null === $upstreamChain) {
                    // If there is no upstream chain, we can't catch exceptions
                    throw new InvalidConfigException('FailTo modifier catches failures that may be thrown by previous Casters, therefore it should not be used as the first element of a chain. Use FailNextTo instead.');
                }

                // Wrap upstream chain execution in a try-catch block
                // to handle exceptions thrown upstream
                return function (mixed $value) use ($upstreamChain, $handler, $dto): mixed {
                    try {
                        // execute upstream chain and return value
                        return $upstreamChain($value);
                    } catch (ProcessingException $e) {
                        ProcessingContext::pushPropPathNode('Mod\FailTo');

                        return $handler($value, $this->fallback, $e, $dto);
                    }
                };
            },
        );
    }

    protected function resolveHandler(BaseDto $dto): callable
    {
        /** @psalm-var mixed */
        $fallback = $this->fallback;

        if (null === $this->handler) {
            $handler = fn (): mixed => $fallback;
        } elseif (is_array($this->handler) && is_callable($this->handler)) {
            $handler = $this->handler;
        } elseif (is_string($this->handler) && is_callable([$dto, $this->handler])) {
            $handler = [$dto, $this->handler];
        } else {
            $shortName = (new \ReflectionClass($this))->getShortName();
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $jsonSerializedHandler = json_encode($this->handler) ?: '???';
            throw new InvalidArgumentException("Invalid $shortName handler: $jsonSerializedHandler, expected DTO method name or valid [class, staticMethod] callable.");
        }

        return $handler;
    }
}
