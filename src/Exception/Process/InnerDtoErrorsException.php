<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

use Nandan108\DtoToolkit\Core\ProcessingErrorList;

/**
 * Exception thrown when a nested DTO has validation errors during processing.
 * The exception contains a list of the validation errors that occurred in the inner DTO.
 *
 * @psalm-suppress PossiblyUnusedProperty
 *
 * @api
 */
final class InnerDtoErrorsException extends GuardException
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ProcessingErrorList $errorList;

    public function __construct(
        ProcessingErrorList $errorList,
    ) {
        $this->errorList = $errorList;

        parent::__construct(
            template_suffix: 'inner_dto.errors',
            parameters: ['count' => $errorList->count()],
            errorCode: 'guard.inner_dto.errors',
        );
    }

    public static function fromList(ProcessingErrorList $errorList): self
    {
        return new self($errorList);
    }
}
