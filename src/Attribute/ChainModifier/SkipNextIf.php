<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * The SkipNextIf attribute is used to skip the next $count chain elements.
 * By default, $count is -1, which means it will wrap as many chain elements as possible.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class SkipNextIf extends ApplyNextIf
{
    use UsesParamResolver;

    public function __construct(
        public string $condition,
        public int $count = 1,
    ) {
        parent::__construct(
            condition: $condition,
            count: $count,
            negate: true
        );
    }
}
