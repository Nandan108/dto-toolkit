<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

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
    #[\Override]
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        $subchain = CasterChainBuilder::buildCasterSubchain(1, $queue, $dto);
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
