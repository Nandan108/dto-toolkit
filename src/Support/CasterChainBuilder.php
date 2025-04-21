<?php

namespace Nandan108\DtoToolkit\Support;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Core\BaseDto;

final class CasterChainBuilder
{
    public static function buildCasterSubchain(int $length, \ArrayIterator $queue, BaseDto $dto, string $modifier): \Closure
    {
        $subAttrs = self::sliceNextAttributes($queue, $length);
        $count = count($subAttrs);
        if ($length > $count) {
            throw new \InvalidArgumentException("$modifier requested a subchain of {$length} casters but only got {$count}.");
        }
        $subchain = self::buildCasterChain($subAttrs, $dto);

        return $subchain;
    }

    /**
     * Build a chain of casters from the given attributes.
     *
     * @param array   $attributes The attributes to process
     * @param BaseDto $dto        The DTO instance
     *
     * @return \Closure a closure that takes a value to cast and returns the result
     */
    public static function buildCasterChain(array $attributes, BaseDto $dto): \Closure
    {
        $queue = new \ArrayIterator($attributes);
        $chain = fn (mixed $value): mixed => $value;

        while ($queue->valid()) {
            $attr = $queue->current();
            $queue->next();

            if ($attr instanceof CastModifierInterface) {
                $chain = $attr->modify($queue, $chain, $dto);
                continue;
            }

            if ($attr instanceof CastTo) {
                $caster = $attr->getCaster($dto);
                $chain = fn (mixed $value): mixed => $caster($chain($value));
            }
        }

        return $chain;
    }

    /**
     * Slice the next $count attributes from the queue.
     * Any encountered CastModifier attributes are included in the slice, but do not count towards the $count.
     *
     * @param \ArrayIterator $queue The queue of attributes to be processed
     * @param int            $count The number of attributes to slice
     *
     * @return array The sliced attributes
     */
    public static function sliceNextAttributes(\ArrayIterator $queue, int $count): array
    {
        $subset = [];

        for ($i = 0; $i < $count && $queue->valid(); $queue->next()) {
            $next = $queue->current();
            $nextIsACast = $next instanceof CastTo;

            if ($nextIsACast || $next instanceof CastModifierInterface) {
                $subset[] = $next;
                $i += (int) $nextIsACast;
            }
        }

        return $subset;
    }
}
