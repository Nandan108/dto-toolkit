<?php

namespace Nandan108\SymfonyDtoToolkit\Contracts;

interface NormalizesInbound
{
    /**
     * Apply transformations (casting/coerctions) to input values to DTO properties.
     * This happens AFTER validation, since it makes no sense to validate already-coerced values.
     *
     * @param array $props The properties to normalize
     * @return array The normalized key-value pairs
     */
    public function normalizeInbound(): static;
}
