<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Enum\Phase;

interface ScopedPropertyAccessInterface
{
    /**
     * @return string[] List of property names in scope for the given phase
     */
    public function getPropertiesInScope(Phase $phase): array;
}
