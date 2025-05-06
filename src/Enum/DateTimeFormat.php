<?php

namespace Nandan108\DtoToolkit\Enum;

enum DateTimeFormat: string
{
    case ISO = 'Y-m-d\TH:i:sP'; // full ISO 8601
    case DATE_ONLY = 'Y-m-d'; // 2025-05-04
    case SQL = 'Y-m-d H:i:s'; // 2025-05-04 14:30:00
    case TIME_ONLY = 'H:i:s'; // 14:30:00
}
