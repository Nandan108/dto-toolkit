<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

final class TransformException extends ProcessingException
{
    /** @var non-empty-string */
    public const DOMAIN = 'processing.transform';

    /**
     * Create when a transformer class does not implement the expected interface.
     */
    public static function invalidInterface(string $className): self
    {
        return new self(
            template_suffix: 'invalid_interface',
            parameters: ['className' => $className],
            errorCode: 'transform.invalid_interface',
        );
    }
}
