<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Config;

final class InvalidConfigException extends ConfigException
{
    public function __construct(
        string $message,
        protected array $debug = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getDebugInfo(): array
    {
        return $this->debug;
    }
}
