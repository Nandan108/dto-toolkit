<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Support\CasterChainBuilder;

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
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        $subchain = CasterChainBuilder::buildNextSubchain(
            length: $this->count,
            queue: $queue,
            dto: $dto,
            modifier: 'FailNextTo', // for error messages
        );
        // ToDo, if subchain is empty, throw a CastingException
        $handler = $this->getHandler($dto);

        return function (mixed $value) use ($chain, $subchain, $dto, $handler): mixed {
            // get value from upstream
            $value = $chain($value);

            try {
                // catch-and-handle exceptions from the next caster
                return $subchain($value);
            } catch (CastingException $e) {
                return $handler($value, $this->fallback, $e, $dto);
            }
        };
    }
}
