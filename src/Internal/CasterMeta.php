<?php

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;

/**
 * Represents metadata for a single CastTo instance used in a caster chain.
 */
final class CasterMeta implements CasterChainNodeInterface
{
    public function __construct(
        public readonly \Closure $caster, // The actual transformation closure or callable
        public readonly ?object $instance, // The object behind the closure (if any)
        public readonly string $sourceClass, // For debugging: where the caster came from
        public readonly ?string $sourceMethod = null, // Optional: method or other debug info
    ) {
        // no-op to keep psalm happy
        [$this->caster, $this->instance, $this->sourceClass, $this->sourceMethod];
    }

    #[\Override]
    public function getClosure(): \Closure
    {
        return $this->caster;
    }

    #[\Override]
    public function getBuiltClosure(?\Closure $upstream): \Closure
    {
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        $caster = $this->getClosure();

        return (null !== $upstream)
            ? fn (mixed $value): mixed => $caster($upstream($value))
            : $caster;
    }

    #[\Override]
    public function __invoke(mixed $value): mixed
    {
        return $this->getClosure()($value);
    }
}
