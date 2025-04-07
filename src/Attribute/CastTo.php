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
     * @param mixed $value The value to cast
     * @return mixed The casted value
     */
    public function getCaster(BaseDto $dto): mixed
    {
        $method = 'castTo' . ucfirst($this->method);

        if (!method_exists($dto, $method)) {
            throw new \LogicException("Missing method '{$method}' for #[CastTo('{$this->method}')] in " . static::class);
        }

        return fn($value) => $dto->$method($value, ...$this->args);
    }
}
