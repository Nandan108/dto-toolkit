<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingChain;

/**
 * The FailNextTo attribute is used to catch and handle exceptions potentially
 * thrown by the next CastTo attribute in the chain.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailNextTo extends FailTo
{
    public function __construct(
        public readonly mixed $fallback = null,
        public readonly string | array | null $handler = null,
        public readonly int $count = -1,
    ) {
        if (!$this->count) {
            throw new InvalidArgumentException('FailNextTo: $count cannot be zero.');
        }
    }

    #[\Override]
    public function getProcessingNode(BaseDto $dto, ?\ArrayIterator $queue): ProcessingChain
    {
        $handler = $this->resolveHandler($dto);

        return new ProcessingChain(
            queue: $queue,
            dto: $dto,
            count: $this->count,
            nodeName: 'Mod\FailNextTo',
            buildCasterClosure: function (array $chainElements, ?callable $upstreamChain) use ($handler, $dto): \Closure {
                $subchain = ProcessingChain::composeFromNodes($chainElements);

                return function (mixed $value) use ($upstreamChain, $subchain, $handler, $dto): mixed {
                    // get the value from upstream (exceptions thrown there are not the concern of this modifier)
                    $upstreamChain && $value = $upstreamChain($value);

                    // Wrap downstream subchain execution in a try-catch block
                    try {
                        // catch-and-handle exceptions from the next caster
                        return $subchain($value);
                    } catch (ProcessingException $e) {
                        ProcessingContext::pushPropPathNode('Mod\FailNextTo');

                        return $handler($value, $this->fallback, $e, $dto);
                    }
                };
            },
        );
    }
}
