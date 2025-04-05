<?php

namespace Nandan108\SymfonyDtoToolkit\Contracts;

interface NormalizesOutbound
{
    /**
     * Apply outbound transformations to DTO values before mapping to array/entity.
     *
     * @param array $props The properties to normalize
     * @return array The normalized key-value pairs
     */
    public function normalizeOutbound(array $props): array;
}
