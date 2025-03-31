<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \RuntimeException implements HttpExceptionInterface
{
    public ConstraintViolationListInterface $violations;

    /**
     * Returns the status code.
     */
    public function getStatusCode() : int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * Returns response headers.
     */
    public function getHeaders(): array
    {
        return [];
    }

    protected $message = 'Validation failed';

    public function __construct(ConstraintViolationListInterface $violations)
    {
        $this->violations = $violations;

        $messages = [];
        foreach ($violations as $v) {
            $messages[] = sprintf('%s: %s', $v->getPropertyPath(), $v->getMessage());
        }

        parent::__construct('Validation failed: ' . implode('; ', $messages));
    }
}
