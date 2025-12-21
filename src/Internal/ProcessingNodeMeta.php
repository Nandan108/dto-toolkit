<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;

/**
 * Represents metadata for a single processing node (cast/validate/etc.) used in a chain.
 */
final class ProcessingNodeMeta implements ProcessingNodeInterface
{
    public function __construct(
        public readonly \Closure $callable, // The transformation/validation closure
        public readonly ?object $instance, // The object behind the closure (if any)
        public readonly string $sourceClass, // For debugging: where the node came from
        public readonly ?string $sourceMethod = null, // Optional: method or other debug info
    ) {
        // no-op to keep psalm happy
        [$this->callable, $this->instance, $this->sourceClass, $this->sourceMethod];
    }

    #[\Override]
    public function getClosure(): \Closure
    {
        return $this->callable;
    }

    #[\Override]
    public function getBuiltClosure(?\Closure $upstream): \Closure
    {
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $callable = $this->getClosure();

        return (null !== $upstream)
            ? fn (mixed $value): mixed => $callable($upstream($value))
            : $callable;
    }

    #[\Override]
    public function __invoke(mixed $value): mixed
    {
        return $this->getClosure()($value);
    }
}
