<?php

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Core\BaseDto;

final class CasterChain implements CasterChainNodeInterface
{
    /** @var CasterChainNodeInterface[] */
    private array $childNodes = [];

    private ?\Closure $compiled = null;

    /**
     * Closure that builds this node's final casting chain from its children.
     *
     * Defaults to [$this, 'composeFromNodes'] unless overridden (e.g. by chain modifiers).
     *
     * @var \Closure(CasterChainNodeInterface[], ?\Closure): \Closure
     */
    public $buildCasterClosure;

    /**
     * CasterChain constructor.
     *
     * @param ?\ArrayIterator<int, CasterChainNodeProducerInterface>     $queue
     * @param int                                                        $count              The number of elements (sub-nodes) to include in this chain node
     * @param string                                                     $className          Debugging info: name of the class that's creating this chain
     * @param ?\Closure(CasterChainNodeInterface[], ?\Closure): \Closure $buildCasterClosure A \Closure that builds the final casting chain from consumed nodes.
     *                                                                                       Defaults to [$this, 'composeFromNodes'] unless overridden (e.g. by chain modifiers).
     *
     * @throws \LogicException
     */
    public function __construct(
        ?\ArrayIterator $queue,
        BaseDto $dto,
        int $count = -1,
        public string $className = 'Caster',
        ?\Closure $buildCasterClosure = null,
    ) {
        $queue ??= new \ArrayIterator();
        for ($i = $count; 0 !== $i && $queue->valid(); --$i) {
            // Recursively consume the next logical chain
            /** @var CasterChainNodeProducerInterface */
            $current = $queue->current();
            $queue->next();
            $this->childNodes[] = $current->getCasterChainNode($dto, $queue);
        }
        if ($i > 0) {
            $getClassName = function (CasterChainNodeInterface $node): string {
                if ($node instanceof CasterChain) {
                    return $node->className;
                }
                $class = $node instanceof CasterMeta ? $node->instance::class : $node::class;
                $i = strrpos($class, '\\');

                return false === $i ? $class : substr($class, $i + 1);
            };

            $childNodesNames = $this->childNodes
                ? 'only '.count($this->childNodes).': ['.implode(', ', array_map($getClassName, $this->childNodes)).']'
                : 'none';
            throw new \LogicException("#[$className] expected $count child nodes, but found $childNodesNames.");
        }

        // if no builder was provided, use $this->composeFromNodes()
        if (null === $buildCasterClosure) {
            $buildCasterClosure =
                /** @param CasterChainNodeInterface[] $chainElements */
                fn (array $chainElements, ?\Closure $upstreamChain): \Closure => // Compose a chain from the child nodes
                    $this->composeFromNodes($chainElements, $upstreamChain);
        }

        $this->buildCasterClosure = $buildCasterClosure;
    }

    #[\Override]
    public function getClosure(): \Closure
    {
        return $this->getBuiltClosure();
    }

    #[\Override]
    public function getBuiltClosure(?\Closure $upstream = null): \Closure
    {
        if (null === $this->compiled) {
            $this->compiled = ($this->buildCasterClosure)($this->childNodes, $upstream);
        }

        return $this->compiled;
    }

    /**
     * Compose a chain from an array of CasterChainNodeInterface.
     *
     * @param CasterChainNodeInterface[] $nodes
     */
    public static function composeFromNodes(array $nodes, ?\Closure $upstream = null): \Closure
    {
        $chain = $upstream;

        while ($node = array_shift($nodes)) {
            $chain = $node->getBuiltClosure(upstream: $chain);
        }

        return $chain ?? fn (mixed $value): mixed => $value;
    }

    /**
     * Recursively walks all nested chain elements and applies a callback
     * to those matching the given type (if provided).
     *
     * @template T of object
     *
     * @param \Closure(CasterChainNodeInterface):void $callback   a function that receives each matching element
     * @param class-string<T>|null                    $typeFilter only elements matching this type will be passed to the callback
     * @param int                                     $maxDepth   maximum recursion depth (default -1 = unlimited)
     */
    public function recursiveWalk(\Closure $callback, ?string $typeFilter = null, int $maxDepth = -1): void
    {
        foreach ($this->childNodes as $element) {
            // If it matches the type filter, apply callback
            if (null === $typeFilter || is_a($element, $typeFilter)) {
                $callback($element);
            }

            // If the element is a nested chain, recurse
            if ($element instanceof self && 0 !== $maxDepth) {
                $element->recursiveWalk($callback, $typeFilter, $maxDepth - 1);
            }
        }
    }

    #[\Override]
    public function __invoke(mixed $value): mixed
    {
        return $this->getClosure()($value);
    }
}
