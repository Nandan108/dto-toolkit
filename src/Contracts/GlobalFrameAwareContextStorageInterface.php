<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/**
 * Optional capability for ContextStorage implementations that can determine
 * whether any processing frames are active across all runtime contexts
 * (for example Fibers/coroutines/tasks), not just the current one.
 *
 * @api
 */
interface GlobalFrameAwareContextStorageInterface
{
    /**
     * True if any processing frame is active in any runtime context.
     */
    public function hasFramesGlobally(): bool;
}
