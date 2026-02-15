<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Context\ContextException;

/** @api */
interface ContextStorageInterface
{
    public function pushFrame(ProcessingFrame $frame): void;

    public function popFrame(): void;

    public function hasFrames(): bool;

    /**
     * @throws ContextException
     */
    public function currentFrame(): ProcessingFrame;

    /**
     * Get the list of processing frames in the context stack, from outermost to innermost.
     *
     * @return list<ProcessingFrame>
     */
    public function frames(): array;
}
