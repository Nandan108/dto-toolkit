<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts;
use Nandan108\DtoToolkit\Traits;

/**
 * @psalm-api
 */
class FullDto extends BaseDto implements // -- --- IGNORE ---
    Contracts\ProcessesInterface, // enables normalization/casting
    Contracts\ScopedPropertyAccessInterface, // for phase-scoping properties
    Contracts\HasContextInterface, // for holding context
    Contracts\HasGroupsInterface, // for group scoping
    Contracts\Injectable // for injecting dependencies marked with #[Inject]
{
    // use Traits\HasContext; // for holding context
    use Traits\UsesGroups; // for phase-scoping properties and casters
    use Traits\CreatesFromArrayOrEntity; // for creating DTOs from arrays
    use Traits\ProcessesFromAttributes; // for casting/transforming properties
    use Traits\ExportsOutbound; // for exporting DTOs to entities
    use Traits\IsInjectable; // for injecting dependencies marked with #[Inject]
}
