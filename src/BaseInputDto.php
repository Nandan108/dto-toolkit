<?php

namespace App\Dto;

use App\Attribute\CastTo;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use Closure;
use LogicException;
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
     * @param object|string|null $objectOrClass defaults to the current instance
     * @return array
     */
    protected function getPublicPropNames(object|string $objectOrClass = null): array
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
        return $this->fillable ??= $this->getPublicPropNames($this);
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
            array: $this->inputSources,
            callback: fn($carry, $source) =>
            array_merge($carry, match ($source) {
                'COOKIE' => $request->cookies->all(),
                'POST'   => $request->request->all(),
                'PARAMS' => $request->attributes->all(),
                'GET'    => $request->query->all(),
                default  => [],
            }),
            initial: [],
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

        return $dto
            // validate raw input values and throw appropriately in case of violations
            ->validated($groups)
            // cast the values to their respective types and return the DTO
            ->normalizeToDto();
    }

    /**
     * Get the attribute casts for the properties
     *
     * @return array
     */
    protected function getAttributeCasts(string $phase = CastTo::DTO): array
    {
        // Cache the casts to avoid reflection overhead
        static $cache = [];

        $class = static::class . "::" . $phase;
        if (isset($casts[$class])) {
            return $cache[$class];
        }

        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(CastTo::class) as $attr) {
                $instance = $attr->newInstance();
                if ($instance->phase === $phase) {
                    $casts[$property->getName()] = $instance->method;
                }
            }
        }

        return $cache[$class] = $casts ?? [];
    }

    /**
     * Cast properties to their respective types
     * For instance, to convert 'string' to 'int' or 'DateTimeImmutable'
     */
    protected function normalizeToDto(?array $castsOverride = null): static
    {
        // Get the casts for the properties
        $casts = $castsOverride ?? $this->getAttributeCasts(CastTo::DTO);

        // Cast the properties to their respective types
        foreach ($casts as $prop => $method) {
            if ($this->filled[$prop]) {
                $this->$prop = $this->$method($this->$prop);
            }
        }

        return $this;
    }

    // Apply entity-level casts
    /**
     * Apply casts (and possibly other transformations in subclasses) to
     * properties in preparation to creating an entity instance.
     * @param array $props
     * @return array
     */
    protected function normalizeToEntity(?array $props = null): array
    {
        // Props (key/vals) default to include all filled DTO fields
        $props ??= $this->toArray(array_keys($this->filled));

        foreach ($this->getAttributeCasts(CastTo::ENTITY) as $prop => $method) {
            if (isset($props[$prop])) {
                $props[$prop] = $this->$method($props[$prop]);
            }
        }

        return $props;
    }

    /**
     * Convert the DTO to an entity
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @throws LogicException
     * @return object
     */
    public function toEntity(array $context = []): object
    {
        $entity = new static::$entityClass();

        // Get properties already type-cast, ready to to be set on entity
        $props = [...$this->normalizeToEntity(), ...$context];

        $setters = $this->getEntitySetterMap($props, $entity);

        // Merge in context props (relations, injected domain values)
        foreach ($props as $prop => $value) {
            $setters[$prop]($value);
        }

        return $entity;
    }


    /**
     *
     * @param null|array $props
     * @param object $entity
     * @return Closure[]
     * @throws LogicException
     */
    protected function getEntitySetterMap(?array $props = null, object $entity): array
    {
        static $setterMap = [];
        $classSetters = $setterMap[static::$entityClass] ??= [];

        $entityReflection = new \ReflectionClass(static::$entityClass);

        $map = [];
        foreach ($props as $prop) {
            if (isset($classSetters[$prop])) {
                $map[$prop] = $classSetters[$prop];
                continue;
            }
            try {
                // Here we assume that DTO and entity have the same property names
                // and that the entity has a setter for each property
                if ($entityReflection->getMethod($setter = 'set' . ucfirst($prop))->isPublic()) {
                    $setterMap[static::$entityClass][$prop] = $map[$prop] =
                        static function (mixed $value) use ($entity, $setter) {
                            $entity->$setter($value);
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
                // No-op, fallback check below
            }

            try {
                if ($entityReflection->getProperty($prop)->isPublic()) {
                    $setterMap[static::$entityClass][$prop] = $map[$prop] =
                        static function (mixed $value) use ($entity, $prop) {
                            $entity->$prop = $value;
                        };
                    continue;
                }
            } catch (\ReflectionException $e) {
                throw new LogicException("No public setter or property found for '{$prop}' in " . static::$entityClass);
            }
        }

        return $map;
    }

    /**
     * Return an array of DTO properties corresponding to the given property names.
     * If no property names are given, all public properties will be returned.
     *
     * @param string[] $propNames
     * @return array
     */
    public function toArray(?array $propNames = null): array
    {
        $vars = get_object_vars($this);
        $keys = $propNames ?? $this->getFillable();

        return array_intersect_key($vars, array_flip($keys));
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
                throw new LogicException('Invalid date format');
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

    /**
     * Convert a value to an integer or null
     *
     * @param mixed $value
     * @return int|null
     */
    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int)$value : null;
    }
}
