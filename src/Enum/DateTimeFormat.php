<?php

namespace Nandan108\DtoToolkit\Enum;

enum DateTimeFormat: string
{
    /** Same as \DateTime::ATOM, \DateTime::RFC3339, \DateTime::W3C */
    case ISO_8601 = 'Y-m-d\TH:i:sP';

    case DATE_ONLY = 'Y-m-d'; // 2025-05-04

    case SQL = 'Y-m-d H:i:s'; // 2025-05-04 14:30:00

    case TIME_ONLY = 'H:i:s'; // 14:30:00

    case ISO_NO_TZ = 'Y-m-d\TH:i:s';

    /** For RSS and HTTP headers. Same as RFC1036, RFC1123, RFC2822, RFC822 */
    case RFC_2822 = 'D, d M Y H:i:s O'; // Sun, 04 May 2025 14:30:00 +0000

    /** For setting cookies: 'l, d-M-Y H:i:s T' */
    case COOKIE = \DateTime::COOKIE;

    /** Same as ISO_8601 but with microseconds: 'Y-m-d\TH:i:s.vP' */
    case RFC3339_EXTENDED = \DateTime::RFC3339_EXTENDED;

    /** For HTTP 1.1 headers, with hardcoded GMT timezone */
    case RFC7231 = \DateTime::RFC7231;

    /** Unix timestamps = seconds since epoch (1970-01-01 00:00:00 UTC) */
    case TIMESTAMP = 'U';

    /** For human-readable output, prefer LocalizedDateTime caster */
    case HUMAN_SHORT = 'M j, Y'; // May 4, 2025

    /** For human-readable output, prefer LocalizedDateTime caster */
    case HUMAN_LONG = 'l, F j, Y g:i A'; // Sunday, May 4, 2025 2:30 PM
}
