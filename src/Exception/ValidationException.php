<?php

namespace Nandan108\SymfonyDtoToolkit\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \RuntimeException implements HttpExceptionInterface
{
    /**
     * Returns the status code.
     *
     * @return int
     */
    #[\Override]
    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * Returns headers to be added to http response.
     */
    #[\Override]
    public function getHeaders(): array
    {
        return [];
    }

    public function __construct(
        public ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Validation failed');
    }
}
