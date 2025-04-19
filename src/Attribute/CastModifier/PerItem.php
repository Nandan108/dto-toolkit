<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

use Mockery\Generator\StringManipulation\Pass\ClassNamePass;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Support\CasterChainBuilder;

/**
 * The PerItem attribute is used to apply the next $count CastTo attributes
 * on each element of the passed array value instead of the whole value.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class PerItem implements CastModifierInterface
{
    public function __construct(
        public readonly int $count = 1,
        public readonly bool $outbound = false,
    ) {}

    #[\Override]
    function isOutbound(): bool
    {
        return $this->outbound;
    }

    /**
     * @param \ArrayIterator $queue The queue of attributes to be processed
     * @param \Closure $chain The chain of callables to be executed
     * @param BaseDto $dto The DTO instance
     * @return \Closure A closure that applies the composed caster on each element of the passed array value
     */
    #[\Override]
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        // Grab a subchain made of the next $this->count CastTo attributes from the queue
        $subchain = CasterChainBuilder::buildCasterSubchain($this->count, $queue, $dto);

        // Into the chain, we now insert our logic, which is to:
        // apply the composed caster ($subchain) on each element of the array value we
        // receive from earlier transformations by $chain().
        return function (mixed $value) use ($chain, $subchain): array {
            // get value from upstream
            $value = $chain($value);

            // If the value is not an array, we throw!
            if (!is_array($value)) {
                throw CastingException::castingFailure(
                    messageOverride: 'PerItem modifier expected an array value, received ' . gettype($value),
                    className: get_class($this),
                    operand: $value,
                    args: ['count' => $this->count],
                );
            }

            return array_map($subchain, $value);
        };
    }
}
