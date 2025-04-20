<?php

namespace Nandan108\DtoToolkit\Contracts;

/**
 * Interface for classes that need to normalize inbound data.
 *
 * This interface is used to define a method for normalizing input values
 * to DTO properties after validation.
 *
 * @psalm-suppress UnusedClass
 */
interface NormalizesInboundInterface
{
    /**
     * Apply transformations (casting/coerctions) to input values to DTO properties.
     * This happens AFTER validation, since it makes no sense to validate already-coerced values.
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function normalizeInbound();
}
