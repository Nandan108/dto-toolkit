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

    /**
     * List of sources to get the input from.
     * Values possible: COOKIE, POST, PARAMS, GET
     * All sources will be visited in the order they're given, and merged with the result.
     * This means that if a value is present in multiple sources, the last one will be used.
     *
     * @var array
     */
    protected array $inputSources = ['POST'];

    /** @var array[string] */
    protected ?array $fillable = null;

    /** @var array[string] */
    public array $filled = [];

    /** @var array[string] */
    protected array $casts = [];

    /** @var class-string */
    protected static $entityClass;

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

    /**
     * Get the fillable properties of the DTO
     *
     * @return array
     */
    protected function getFillable(): array
    {
        return $this->fillable ??= $this->getPropNames($this);
    }

    /**
     * Validate the DTO
     *
     * @param array|null $groups
     * @throws ValidationException
     * @return static
     */
    public function validated(array $groups = null): static
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
                }),
            [],
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
    public static function fromRequest(Request $request, array $groups = null): static
    {
        $dto = new static();

        foreach ($dto->getFillableInput($request) as $property => $value) {
            $dto->{$property}       = $value;
            $dto->filled[$property] = true;
        }

        // Allow subclasses to preprocess values

        return $dto->validated($groups)->normalize();
    }

    /**
     * Cast properties to their respective types
     * For instance, to convert 'string' to 'int' or 'DateTimeImmutable'
     */
    protected function normalize(array $fieldCasts = []): static
    {
        $fieldCasts = array_merge($this->casts ?? [], $fieldCasts);

        foreach ($fieldCasts as $property => $method) {
            if ($this->filled[$property]) {
                $this->$property = $this->$method($this->$property);
            }
        }

        return $this;
    }

    protected function modifyEntity(object $entity): void
    {
        // No-op in base class
    }

    /**
     * Convert the DTO to an entity
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @throws \LogicException
     * @return object
     */
    public function toEntity(): object
    {
        if (!isset(static::$entityClass)) {
            throw new \LogicException(static::class . ' must define a static $entityClass');
        }

        // Create a new instance of the entity class
        $entity = new static::$entityClass();
        // Get the public properties of the DTO subclass
        $fillable = $this->getFillable();
        // Get the public properties of the entity class
        $targetProps = $this->getPropNames($entity);
        // Get the intersection of the two arrays
        $settableProps = array_intersect($fillable, $targetProps);

        // Set the properties on the entity
        foreach ($settableProps as $prop) {
            if (property_exists($this, $prop)) {
                $entity->{'set' . ucfirst($prop)}($this->$prop);
            }
        }

        // Allow subclasses to preprocess values
        $this->modifyEntity($entity);

        return $entity;
    }

    public function toArray(): array
    {
        return array_intersect_key(
            get_object_vars($this),
            array_flip($this->getFillable()),
        );
    }

    /**
     * Convert a value to an integer or null
     *
     * @param mixed $value
     * @return int|null
     */
    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Convert a value to a DateTimeImmutable or null
     *
     * @param mixed $value
     * @return \DateTimeImmutable|null
     */
    protected function dateTimeOrNull(?string $value): ?\DateTimeImmutable
    {
        if (is_string($value) && $value !== '') {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Exception) {
                throw new \LogicException('Invalid date format');
            }
        } elseif ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        return null;
    }

    /**
     * Convert a value to a string or null
     *
     * @param mixed $value
     * @return string|null
     */
    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
