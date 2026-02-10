<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface ErrorMessageRendererInterface
{
    public function render(ProcessingExceptionInterface $exception): string;
}
