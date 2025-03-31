<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use ReflectionProperty;

abstract class BaseInputDto
{
    private static ?ValidatorInterface $validator = null;

    /** @var array[string] */
    protected array $fillable = [];

    /** @var array[string] */
    public array $filled = [];

    protected function getFillable() {
        if (!$this->fillable) {
            $fillableProperties = (new \ReflectionClass($this))
                ->getProperties(ReflectionProperty::IS_PUBLIC);

            $this->fillable = array_map(
                fn(ReflectionProperty $property) => $property->getName(),
                $fillableProperties
            );
        }
        return $this->fillable;
    }

    public function validated(array $groups = null): static
    {
        $violations = self::getValidator()->validate($this, null, $groups);

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

    public static function getRequestInput($request): array {
        return array_merge(
            $request->attributes->all(),
            $request->query->all(),
            $request->request->all()
        );
    }

    public function getFillableDataFromRequest($request): array {
        return array_intersect_key(
            self::getRequestInput(request: $request),
            array_flip($this->getFillable())
        );
    }

    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        foreach ($dto->getFillableDataFromRequest($request) as $property => $value) {
            $dto->{$property} = $value;
            $dto->filled[$property] = true;
        }

        return $dto->validated();
    }
}
