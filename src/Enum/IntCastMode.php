<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Enum;

/** @api */
enum IntCastMode: string
{
    case Trunc = 'trunc';
    case Floor = 'floor';
    case Ceil = 'ceil';
    case Round = 'round';
}
