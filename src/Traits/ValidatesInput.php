<?php

namespace Nandan108\SymfonyDtoToolkit\Traits;

use Nandan108\SymfonyDtoToolkit\Exception\ValidationException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Validates input using Symfony Validator
 *
 * @package Nandan108\SymfonyDtoToolkit\Traits
 * @property array $filled
 */
trait ValidatesInput
{
    private static ?ValidatorInterface $validator = null;

    /**
     * Validate the input using Symfony Validator
     *
     * @param string|GroupSequence|array|null $groups
     * @return static
     * @throws ValidationException
     */
    public function validate(string|GroupSequence|array|null $groups = null): static
    {
        $violations = self::getValidator()->validate($this, null, $groups);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $this;
    }

    /**
     * Get the validator instance
     *
     * @return ValidatorInterface
     */
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
