<?php

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts;
use Nandan108\DtoToolkit\Traits;

/**
 * @psalm-api
 */
class FullDto extends BaseDto implements // --
    Contracts\NormalizesInboundInterface, // enables inbound casting (normalization)
    Contracts\NormalizesOutboundInterface, // enables outbound casting
    Contracts\ScopedPropertyAccessInterface, // for phase-scoping properties
    Contracts\HasGroupsInterface
{
    use Traits\UsesGroups; // for phase-scoping properties and casters
    use Traits\CreatesFromArray; // for creating DTOs from arrays
    use Traits\NormalizesFromAttributes; // for casting/transforming properties
    use Traits\ExportsToEntity; // for exporting DTOs to entities
}
