<?php

namespace App\Dto;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;

abstract class BaseInputDto
{
    private static ?ValidatorInterface $validator = null;

    public function validated(): static
    {
        $violations = self::getValidator()->validate($this);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $this;
    }

    private static function getValidator(): ValidatorInterface
    {
        if (!self::$validator) {
            self::$validator = Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator();
        }

        return self::$validator;
    }
}
