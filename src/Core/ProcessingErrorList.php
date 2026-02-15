<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

/**
 * @implements \IteratorAggregate<int, ProcessingException>
 *
 * @api
 */
final class ProcessingErrorList implements \Countable, \IteratorAggregate
{
    /** @var list<ProcessingException> */
    private array $errors = [];

    public function add(ProcessingException $e): void
    {
        $this->errors[] = $e;
    }

    /**
     * @return list<ProcessingException>
     */
    public function all(): array
    {
        return $this->errors;
    }

    public function isEmpty(): bool
    {
        return [] === $this->errors;
    }

    public function clear(): void
    {
        $this->errors = [];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    #[\Override]
    public function count(): int
    {
        return \count($this->errors);
    }

    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->errors);
    }
}
