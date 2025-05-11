<?php

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeInterface;
use Nandan108\DtoToolkit\Contracts\ChainModifierInterface;
use Nandan108\DtoToolkit\Core\BaseDto;

class CasterChain implements CasterChainNodeInterface
{
    /** @var CasterChainNodeInterface[] */
    private array $chainElements = [];

    private ?\Closure $compiled = null;

    /**
     * Closure that builds this node's final casting chain from its children.
     *
     * Defaults to [$this, 'composeFromNodes'] unless overridden (e.g. by chain modifiers).
     *
     * @var callable
     */
    public $buildCasterClosure;

    public function __construct(
        \ArrayIterator $queue,
        private BaseDto $dto,
        int $count = -1,
        string $class = 'Caster',
        /** @var ?callable $buildCasterClosure */
        ?callable $buildCasterClosure = null,
    ) {
        for ($i = $count; 0 !== $i && $queue->valid(); --$i) {
            // Recursively consume the next logical chain
            $this->chainElements[] = $this->buildNextChainElement($queue);
        }
        if ($i > 0) {
            throw new \LogicException("#[$class] expected $count cast chains, but only found ".count($this->chainElements).'.');
        }

        $this->buildCasterClosure = $buildCasterClosure ?? [$this, 'composeFromNodes'];
    }

    #[\Override]
    public function getClosure(): callable
    {
        return $this->getBuiltClosure();
    }

    #[\Override]
    public function getBuiltClosure(?callable $upstream = null): callable
    {
        return $this->compiled ??= ($this->buildCasterClosure)($this->chainElements, $upstream);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @return CasterChainNodeInterface[]
     **/
    public function getChainElements(): array
    {
        return $this->chainElements;
    }

    private function buildNextChainElement(\ArrayIterator $queue): CasterChainNodeInterface
    {
        $current = $queue->current();
        $queue->next();

        if ($current instanceof ChainModifierInterface) {
            return $current->getModifier($queue, $this->dto);
        }

        if ($current instanceof CastTo) {
            return $current->getCaster($this->dto); // returns CasterMeta
        }

        throw new \LogicException('Unexpected attribute in caster queue: '.get_debug_type($current));
    }

    /**
     * Compose a chain from an array of CasterChainNodeInterface.
     *
     * @param CasterChainNodeInterface[] $nodes
     * @param ?\Closure                  $upstream
     */
    public static function composeFromNodes(array $nodes, ?callable $upstream = null): callable
    {
        $chain = $upstream;

        while ($node = array_shift($nodes)) {
            $chain = $node->getBuiltClosure(upstream: $chain);
        }

        return $chain ?? fn (mixed $value): mixed => $value;
    }

    #[\Override]
    public function __invoke(mixed $value): mixed
    {
        return $this->getClosure()($value);
    }
}
