<?php

namespace Nandan108\DtoToolkit\Support;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Core\BaseDto;

final class CasterChainBuilder
{
    /**
     * Build a subchain of casters from the given attributes.
     *
     * @param int      $length  The number of casters to build
     * @param \ArrayIterator $queue   The queue of attributes to process
     * @param BaseDto  $dto     The DTO instance
     * @param string   $modifier The name of the modifier for error messages
     *
     * @return \Closure a closure that takes a value to cast and returns the result
     */
    public static function buildNextSubchain(int $length, \ArrayIterator $queue, BaseDto $dto, string $modifier = 'unknown' ):\Closure {
        $subs = [];

        // gather the next $length subchains
        for ($i = 0; $i < $length; $i++) {
            if (!$queue->valid()) {
                throw new \InvalidArgumentException(sprintf(
                    '%s requested %d casters, but only found %d.',
                    $modifier, $length, $i
                ));
            }

            $subs[] = self::buildChainRecursive($queue, $dto, count: 1);
        }

        return self::composeChain($subs);
    }

    /**
     * Build a chain of casters from the given attributes.
     * Entry point to chain building, called by CastTo::getCastingClosureMap()
     *
     * @param array   $attributes The attributes to process
     * @param BaseDto $dto        The DTO instance
     *
     * @return \Closure a closure that takes a value to cast and returns the result
     */
    public static function buildCasterChain(array $attributes, BaseDto $dto): \Closure
    {
        $queue = new \ArrayIterator($attributes);
        return self::buildChainRecursive($queue, $dto);
    }

    private static function buildChainRecursive(\ArrayIterator $queue, BaseDto $dto, ?int $count = -1): \Closure
    {
        $chain = fn (mixed $value): mixed => $value;

        while ($queue->valid() && $count--) {
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


    public static function composeChain(array $subchains): \Closure
    {
        return array_reduce($subchains, fn($carry, $next) =>
            fn(mixed $value): mixed => $next($carry($value)),
            fn(mixed $value): mixed => $value
        );
    }
}
