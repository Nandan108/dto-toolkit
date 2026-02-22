<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute\ChainModifier;

/**
 * This is suggar for Wrap(0): a simple no-op that doesn't modify the chain.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class NoOp extends Wrap
{
    public function __construct()
    {
        parent::__construct(count: 0);
    }
}
