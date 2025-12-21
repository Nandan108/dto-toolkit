<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

use Nandan108\PropPath\Support\ExtractContext;

final class ExtractionException extends ProcessingException
{
    public static function extractFailed(
        string $methodOrClass,
        string $message,
        ?ExtractContext $context = null,
    ): self {
        $value = $context->roots['value'] ?? null;

        return new static(
            template_suffix: 'extract.failed',
            parameters: [
                'methodOrClass' => $methodOrClass,
            ],
            debug: [
                'message'    => $message,
                'value'      => self::prepareOperandForDebug($value),
                'orig_value' => $value,
                'context'    => $context?->getEvalErrorMessage($message) ?? $message,
            ],
        );
    }
}
