<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

use Nandan108\PropPath\Support\EvaluationFailureDetails;

/**
 * @internal internal processing exception; not part of public API
 *
 * @psalm-internal Nandan108\DtoToolkit
 *
 * @phpstan-internal Nandan108\DtoToolkit
 */
final class ExtractionException extends ProcessingException
{
    public readonly EvaluationFailureDetails $failure;

    public function __construct(
        string $message,
        EvaluationFailureDetails $failure,
        string $errorCode = 'processing.extract_failure',
    ) {
        $this->failure = $failure;

        parent::__construct(
            template_suffix: 'extract.failed',
            parameters: [
                'message'    => $message,
                'failedPath' => $failure->getPropertyPath(),
            ],
            debug: [
                'failure'    => $failure,
            ],
            errorCode: $errorCode,
        );
    }

    public static function extractFailed(
        string $message,
        EvaluationFailureDetails $failure,
        string $errorCode = 'processing.extract_failure',
    ): self {
        return new self(
            message: $message,
            failure: $failure,
            errorCode: $errorCode,
        );
    }
}
