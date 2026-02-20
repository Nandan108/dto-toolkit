<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Enum\Phase;

/** @api */
interface ScopedPropertyAccessInterface
{
    /**
     * @return list<truthy-string> List of property names in scope for the given phase
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getPropertiesInScope(Phase $phase): array;
}
