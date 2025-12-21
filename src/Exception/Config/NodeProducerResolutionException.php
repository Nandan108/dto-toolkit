<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Config;

final class NodeProducerResolutionException extends ConfigException
{
    public function __construct(string $reference, ?string $reason = null)
    {
        $message = "Unable to resolve processing node producer '{$reference}'.";

        if (null !== $reason && '' !== $reason) {
            $message .= " Reason: {$reason}.";
        }

        parent::__construct($message);
    }

    public static function for(string $reference, ?string $reason = null): self
    {
        return new self($reference, $reason);
    }
}
