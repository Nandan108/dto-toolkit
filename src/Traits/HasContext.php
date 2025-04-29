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

    public function getContextMap(): array
    {
        return $this->_context;
    }
}
