<?php

namespace Nandan108\DtoToolkit\Contracts;

interface NormalizesInboundInterface
{
    /**
     * Apply transformations (casting/coerctions) to input values to DTO properties.
     * This happens AFTER validation, since it makes no sense to validate already-coerced values.
     *
     * @return void
     */
    public function normalizeInbound();
}
