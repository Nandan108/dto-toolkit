<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Internal\CasterChain;

/**
 * The FailNextTo attribute is used to catch and handle exceptions potentially
 * thrown by the next CastTo attribute in the chain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailNextTo extends FailTo
{
    public function __construct(
        public readonly mixed $fallback = null,
        public readonly string|array|null $handler = null,
        public readonly int $count = 1,
    ) {
        if ($this->count < 1) {
            throw new \InvalidArgumentException('FailNextTo: $count must be greater than or equal to 1.');
        }
    }

    #[\Override]
    public function getModifier(\ArrayIterator $queue, BaseDto $dto): CasterChain
    {
        $handler = $this->resolveHandler($dto);

        return new CasterChain($queue, $dto, $this->count, class: 'FailNextTo',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain) use ($handler, $dto): \Closure {
                $subchain = CasterChain::composeFromNodes($chainElements);

                return function (mixed $value) use ($upstreamChain, $subchain, $handler, $dto): mixed {
                    // get the value from upstream (exceptions thrown there are not the concern of this modifier)
                    if ($upstreamChain) {
                        $value = $upstreamChain($value);
                    }

                    // Wrap downstream subchain execution in a try-catch block
                    try {
                        // catch-and-handle exceptions from the next caster
                        return $subchain($value);
                    } catch (CastingException $e) {
                        return $handler($value, $this->fallback, $e, $dto);
                    }
                };
            }
        );
    }
}
