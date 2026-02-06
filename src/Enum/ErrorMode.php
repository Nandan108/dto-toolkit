<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Enum;

enum ErrorMode: string
{
    /** Default. Exceptions are thrown immediately and processing stops. */
    case FailFast = 'fail_fast';

    /** Exceptions are recorded, and the property value is set to `null`. */
    case CollectFailToNull = 'collect_fail_to_null';

    /** Exceptions are recorded, but the original input value is preserved. */
    case CollectFailToInput = 'collect_fail_to_input';

    /**
     * Exceptions are recorded, and the property is omitted entirely
     * - inbound: property is marked "unfilled"
     * - outbound: output array omits property.
     */
    case CollectNone = 'collect_none';
}
