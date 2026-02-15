<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

/**
 * Represents metadata for a single processing node (cast/validate/etc.) used in a chain.
 *
 * @internal
 */
final class ProcessingNodeMeta implements ProcessingNodeInterface
{
    public readonly \Closure $callable;

    /**
     * @param \Closure       $callable     The transformation/validation closure to be invoked for this node
     * @param ?object        $instance     The object instance behind the closure (if any), used for debugging or context
     * @param truthy-string  $nodeName     The short class name where this node originated, for debugging purposes (e.g. "CastTo\Int")
     * @param ?truthy-string $sourceMethod Optional method name or other debug info about the source of this node
     */
    public function __construct(
        \Closure $callable,
        public readonly ?object $instance,
        public readonly string $nodeName,
        public readonly ?string $sourceMethod = null,
    ) {
        $nodeName = $this->nodeName.($this->sourceMethod ? "::{$this->sourceMethod}" : '');
        $includeTrace = ProcessingContext::includeProcessingTraceInErrors();

        // Centralized exception enrichment:
        // - in trace mode, annotate path with node markers
        // - always ensure ProcessingException carries thrower node name
        $this->callable = function (mixed $value) use ($callable, $nodeName, $includeTrace): mixed {
            if ($includeTrace) {
                ProcessingContext::pushPropPathNode($nodeName);
            }

            try {
                return ($callable)($value);
            } catch (ProcessingException $e) {
                $e->setThrowerNodeNameIfMissing($nodeName);
                throw $e;
            }
        };
    }

    #[\Override]
    public function getName(): string
    {
        return $this->nodeName.($this->sourceMethod ? "::{$this->sourceMethod}" : '');
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
