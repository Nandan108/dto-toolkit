<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Config;

/**
 * Exception thrown when there is a syntax error in the extraction path.
 */
final class InvalidArgumentException extends ConfigException
{
    // need to accept extra $debug parameters
    public function __construct(
        string $message = '',
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
