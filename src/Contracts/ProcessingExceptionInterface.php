<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface ProcessingExceptionInterface extends \Throwable
{
    public function getMessageTemplate(): string;

    /** @return array<string, mixed> */
    public function getMessageParameters(): array;

    public function getPropertyPath(): ?string;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getErrorCode(): string | int | null;
}
