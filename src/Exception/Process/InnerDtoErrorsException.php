<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

use Nandan108\DtoToolkit\Core\ProcessingErrorList;

/** @psalm-suppress PossiblyUnusedProperty */
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
