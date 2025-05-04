<?php

namespace Nandan108\DtoToolkit\Traits;

/**
 * @method static static withContext(array $values)
 */
trait HasContext
{
    protected array $_context = [];

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function setContext(string $key, mixed $value): static
    {
        $this->_context[$key] = $value;

        return $this;
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function unsetContext(string $key): static
    {
        unset($this->_context[$key]);

        return $this;
    }

    public function _withContext(array $values): static
    {
        foreach ($values as $key => $val) {
            $this->setContext($key, $val);
        }

        return $this;
    }

    #[\Override]
    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->_context[$key] ?? $default;
    }

    public function hasContext(string $key, bool $treatNullAsMissing = true): bool
    {
        if (!array_key_exists($key, $this->_context)) {
            return false;
        }

        if ($treatNullAsMissing && null === $this->_context[$key]) {
            return false;
        }

        return true;
    }

    public function getContextMap(): array
    {
        return $this->_context;
    }
}
