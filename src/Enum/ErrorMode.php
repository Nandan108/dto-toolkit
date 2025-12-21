<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Enum;

enum ErrorMode: string
{
    case FailFast = 'fail_fast';
    case CollectFailToNull = 'collect_fail_to_null';
    case CollectFailToInput = 'collect_fail_to_input';
    case CollectNone = 'collect_none';
}
