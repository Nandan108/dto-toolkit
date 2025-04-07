<?php

namespace  Nandan108\SymfonyDtoToolkit\Attribute;

use Attribute;
use Nandan108\SymfonyDtoToolkit\BaseDto;

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
    public function __construct(
        public string $method,
        public bool $outbound = false,
        public array $args = [],
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
        $method = 'castTo' . ucfirst($this->method);

        if (!method_exists($dto, $method)) {
            throw new \LogicException("Missing method '{$method}' for #[CastTo('{$this->method}')] in " . static::class);
        }

        return fn($value) => $dto->$method($value, ...$this->args);
    }

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
