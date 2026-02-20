<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Core\ProcessingContext;

/**
 * @method static static newWithContext(array $values)
 *
 * @api
 */
trait HasContext
{
    /** @var array<non-empty-string, mixed> */
    protected array $_context = [];

    #[\Override]
    /** @param non-empty-string $key */
    public function contextSet(string $key, mixed $value): static
    {
        /** @var non-empty-string $key */
        $this->_context[$key] = $value;

        return $this;
    }

    #[\Override]
    /** @param non-empty-string $key */
    public function contextUnset(string $key): static
    {
        unset($this->_context[$key]);

        return $this;
    }

    /** @param array<non-empty-string, mixed> $values */
    public function withContext(array $values): static
    {
        /** @var mixed $val */
        foreach ($values as $key => $val) {
            $this->contextSet($key, $val);
        }

        return $this;
    }

    #[\Override]
    /** @param non-empty-string $key */
    public function contextGet(string $key, mixed $default = null): mixed
    {
        return $this->_context[$key] ?? $default;
    }

    #[\Override]
    /** @param non-empty-string $key */
    public function contextHas(string $key, bool $treatNullAsMissing = true): bool
    {
        if (!array_key_exists($key, $this->_context)) {
            return false;
        }

        if ($treatNullAsMissing && null === $this->_context[$key]) {
            return false;
        }

        return true;
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    #[\Override]
    public function getContext(): array
    {
        $activeFrame = ProcessingContext::tryCurrent();

        return $activeFrame?->context ?? $this->_context;
    }
}
