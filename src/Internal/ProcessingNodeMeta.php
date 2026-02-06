<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;

/**
 * Represents metadata for a single processing node (cast/validate/etc.) used in a chain.
 */
final class ProcessingNodeMeta implements ProcessingNodeInterface
{
    public readonly \Closure $callable;

    /**
     * @param \Closure       $callable     The transformation/validation closure to be invoked for this node
     * @param ?object        $instance     The object instance behind the closure (if any), used for debugging or context
     * @param truthy-string  $sourceClass  The 2-step short class name where this node originated, for debugging purposes (e.g. "CastTo\Int")
     * @param ?truthy-string $sourceMethod Optional method name or other debug info about the source of this node
     */
    public function __construct(
        \Closure $callable,
        public readonly ?object $instance,
        public readonly string $sourceClass,
        public readonly ?string $sourceMethod = null,
    ) {
        // In dev mode, we push the source class/method info onto the context stack for better error messages and debugging.
        if (ProcessingContext::includeProcessingTraceInErrors()) {
            $nodeName = $this->sourceClass.($this->sourceMethod ? "::{$this->sourceMethod}" : '');
            $this->callable = function (mixed $value) use ($callable, $nodeName): mixed {
                ProcessingContext::pushPropPathNode($nodeName);

                return ($callable)($value);
            };
        } else {
            $this->callable = $callable;
        }
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
