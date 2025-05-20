<?php

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

use Nandan108\DtoToolkit\Traits\UsesParamResolver;

/**
 * This is suggar for Wrap(0): a simple no-op that doesn't modify the chain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class NoOp extends Wrap
{
    use UsesParamResolver;

    public function __construct()
    {
        parent::__construct(count: 0);
    }
}
