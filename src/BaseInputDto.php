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

    protected array $inputSources = ['POST'];

    /** @var array[string] */
    protected ?array $fillable = null;

    /** @var array[string] */
    public array $filled = [];

    /**
     * Get the names of the public properties of an object
     *
     * @param object|null $object defaults to the current instance
     * @return array
     */
    protected function getPropNames(object $object = null): array
    {
        $reflectionClass = new \ReflectionClass($objectOrClass ?? $this);

        $props = [];
        foreach ($reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isPublic()) {
                $props[] = $prop->getName();
            }
        }
        return $props;
    }

    protected function getFillable(): array
    {
        return $this->fillable ??= $this->getPropNames($this);
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

    /**
     * Get the request's input according to the input sources
     *
     * @param Request $request
     * @return array
     */
    public function getRequestInput(Request $request): array
    {
        return array_reduce(
            $this->inputSources,
            fn ($carry, $source) =>
                array_merge($carry, match ($source) {
                    'COOKIE' => $request->cookies->all(),
                    'POST' => $request->request->all(),
                    'PARAMS' => $request->attributes->all(),
                    'GET' => $request->query->all(),
                    default => [],
                })
            ,
            []
        );
    }

    /**
     * Get the fillable data from the request
     *
     * Can be overriden when special treatment is needed.
     *
     * @param Request $request
     * @return array
     */
    public function getFillableInput(Request $request): array
    {
        return array_intersect_key(
            self::getRequestInput(request: $request),
            array_flip($this->getFillable()),
        );
    }

    /**
     * Create a new instance of the DTO from a request
     *
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        $dto = new static();

        foreach ($dto->getFillableInput($request) as $property => $value) {
            $dto->{$property}       = $value;
            $dto->filled[$property] = true;
        }

        return $dto->validated();
    }
}
