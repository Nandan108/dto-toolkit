<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;

/**
 * The FailTo attribute is used to catch and handle exceptions
 * thrown by any caster declared earlier in the chain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailTo extends ChainModifierBase
{
    protected \Closure|array|null $_handler = null;

    public function __construct(
        public readonly mixed $fallback = null,
        public readonly string|array|null $handler = null,
    ) {
    }

    #[\Override]
    public function getCasterChainNode(BaseDto $dto, ?\ArrayIterator $queue): CasterChain
    {
        $handler = $this->resolveHandler($dto);

        return new CasterChain(queue: $queue, dto: $dto, count: 0, className: 'FailTo',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain) use ($handler, $dto): \Closure {
                if (null === $upstreamChain) {
                    // If there is no upstream chain, we can't catch exceptions
                    throw new \LogicException('FailTo modifier catches failures that may be thrown by previous Casters, therefore it should not be used as the first element of a chain. Use FailNextTo instead.');
                }

                // Wrap upstream chain execution in a try-catch block
                // to handle exceptions thrown upstream
                return function (mixed $value) use ($upstreamChain, $handler, $dto): mixed {
                    try {
                        // execute upstream chain and return value
                        return $upstreamChain($value);
                    } catch (CastingException $e) {
                        return $handler($value, $this->fallback, $e, $dto);
                    }
                };
            }
        );
    }

    protected function resolveHandler(BaseDto $dto): callable
    {
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
            throw new \InvalidArgumentException("Invalid $shortName handler: $jsonSerializedHandler, ".'expected DTO method name or valid [class, staticMethod] callable.');
        }

        return $handler;
    }
}
