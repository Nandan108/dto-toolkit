<?php

namespace Nandan108\DtoToolkit\Enum;

enum IntCastMode: string
{
    case Trunc = 'trunc';
    case Floor = 'floor';
    case Ceil = 'ceil';
    case Round = 'round';
}
