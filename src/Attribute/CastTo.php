<?php

namespace  Nandan108\DtoToolkit\Attribute;

use Attribute;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

/**
 * Defines a casting method for a DTO property during normalization.
 *
 * Usage:
 *   #[CastTo('SomeType')]
 *   public string|SomeType|null $property;
 *
 * This will call the method castToSomeType($value) on the DTO.
 *
 * - The provided value must match the suffix of a method named castTo{$value}()
 * - The optional `$outbound` flag specifies if the cast is applied during output
 * normalization, meaning before returning an entity or array.
 */
 #[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CastTo
{
    static protected string $methodPrefix = 'castTo';

    public function __construct(
        public string $methodOrClass,
        /** @psalm-suppress PossiblyUnusedProperty */
        public bool $outbound = false,
        public array $args = [],
        public ?array $constructorArgs = null,
    ) {}

    /**
     * Create a caster closure for the given method
     *
     * @param mixed $dto The DTO instance
     * @throws \LogicException If the method does not exist
     * @return \Closure A closure that takes a value to cast calls the casting method and returns the result.
     */
    public function getCaster(BaseDto $dto): mixed
    {

        if (class_exists($this->methodOrClass)) {
            static $casterCache = [];

            $className = $this->methodOrClass;

            if (!isset($casterCache[$className])) {
                $instance = $this->resolveFromClass($className);
                $casterCache[$className] = ['instance' => $instance, 'casters' => []];
            }

            $serializedArgs = serialize($this->args);
            $instance = $casterCache[$className]['instance'];
            $args = $this->args;
            $caster = $casterCache[$className]['casters'][$serializedArgs] ??=
                function($value) use ($instance, $args) {
                    return $instance->cast($value, ...$args);
                };

            return $caster;
        }

        $method = static::$methodPrefix . ucfirst($this->methodOrClass);

        if (!method_exists($dto, $method)) {
            throw new \LogicException("Missing method '{$method}' for #[CastTo('{$this->methodOrClass}')] in " . static::class);
        }

        return fn($value) => $dto->$method($value, ...$this->args);
    }

    /**
     * Resolve the class name to a CasterInterface instance
     *
     * @param string $className The class name
     * @return CasterInterface The resolved instance
     */
    protected function resolveFromClass(string $className): CasterInterface
    {
        if (!is_subclass_of($className, CasterInterface::class)) {
            throw new \LogicException("Class '$className' must implement " . CasterInterface::class);
        }

        if ($this->constructorArgs !== null) {
            $instance = new $className(...$this->constructorArgs);
        } else {
            $ref = new \ReflectionClass($className);
            $ctor = $ref->getConstructor();

            if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                $instance = $ref->newInstance();
            } else {
                $instance = $this->resolveFromClassWithContainer($className);
            }
        }

        return $instance;
    }

    /**
     * Resolve the class name to a CasterInterface instance using a container
     * To be overriden by the framework-specific implementation
     *
     * @return CasterInterface
     */
    public function resolveFromClassWithContainer(string $className): ?CasterInterface
    {
        throw new \LogicException("Caster {$className} requires constructor args, but none were provided and no container is available.");
    }

    /**
     * Get an associative array of [propName => castingClosure] for a DTO
     *
     * @param \Nandan108\DtoToolkit\BaseDto $dto
     * @param bool $outbound
     * @return array
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getCastingClosureMap(
        BaseDto $dto,
        bool $outbound = false,
    ): array {
        static $cache = [];

        $reflection = new \ReflectionClass($dto);
        $dtoClass = $reflection->getName();
        $casts = &$cache[$dtoClass];

        if (!isset($casts)) {
            $casts = [false => [], true => []];
            foreach ($reflection->getProperties() as $property) {
                foreach ($property->getAttributes(static::class) as $attr) {
                    /** @var CastTo $instance */
                    $instance = $attr->newInstance();
                    $casts[$instance->outbound][$property->getName()] = $instance->getCaster($dto);
                }
            }
        }

        return $casts[$outbound];
    }
}
