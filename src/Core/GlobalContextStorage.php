<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts\ContextStorageInterface;
use Nandan108\DtoToolkit\Exception\Context\ContextException;

final class GlobalContextStorage implements ContextStorageInterface
{
    /** @var list<ProcessingFrame> */
    private array $frames = [];

    #[\Override]
    public function pushFrame(ProcessingFrame $frame): void
    {
        array_unshift($this->frames, $frame);
    }

    #[\Override]
    public function popFrame(): void
    {
        $this->frames || throw new ContextException('Out-of-context call: no ProcessingFrame to pop.');

        array_shift($this->frames);
    }

    #[\Override]
    public function hasFrames(): bool
    {
        return (bool) $this->frames;
    }

    #[\Override]
    public function currentFrame(): ProcessingFrame
    {
        $this->frames || throw new ContextException('Out-of-context call: no current ProcessingFrame.');

        return $this->frames[0];
    }

    /**
     * Get the list of processing frames in the context stack, from outermost to innermost.
     *
     * @return list<ProcessingFrame>
     */
    #[\Override]
    public function frames(): array
    {
        return array_reverse($this->frames);
    }
}
