<?php

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts;
use Nandan108\DtoToolkit\Traits;

/**
 * @psalm-api
 */
class FullDto extends BaseDto implements // --
    Contracts\NormalizesInterface, // enables normalization/casting
    Contracts\ScopedPropertyAccessInterface, // for phase-scoping properties
    Contracts\HasContextInterface, // for holding context
    Contracts\HasGroupsInterface, // for group scoping
    Contracts\Injectable // for injecting dependencies marked with #[Inject]
{
    use Traits\UsesGroups; // for phase-scoping properties and casters
    use Traits\CreatesFromArrayOrEntity; // for creating DTOs from arrays
    use Traits\NormalizesFromAttributes; // for casting/transforming properties
    use Traits\ExportsToEntity; // for exporting DTOs to entities
    use Traits\IsInjectable; // for injecting dependencies marked with #[Inject]
}
