<?php

namespace  Nandan108\SymfonyDtoToolkit\Attribute;

use Attribute;

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
}
