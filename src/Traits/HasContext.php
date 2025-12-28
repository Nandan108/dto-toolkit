<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

/**
 * @method static static newWithContext(array $values)
 */
trait HasContext
{
    protected array $_context = [];

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function contextSet(string $key, mixed $value): static
    {
        $this->_context[$key] = $value;

        return $this;
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function contextUnset(string $key): static
    {
        unset($this->_context[$key]);

        return $this;
    }

    public function withContext(array $values): static
    {
        foreach ($values as $key => $val) {
            $this->contextSet($key, $val);
        }

        return $this;
    }

    #[\Override]
    public function contextGet(string $key, mixed $default = null): mixed
    {
        return $this->_context[$key] ?? $default;
    }

    #[\Override]
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

    #[\Override]
    public function getContext(): array
    {
        return $this->_context;
    }
}
