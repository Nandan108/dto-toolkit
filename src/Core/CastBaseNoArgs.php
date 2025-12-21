<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

/**
 * Base class for Casters that take no arguments and don't need a constructor.
 */
abstract class CastBaseNoArgs extends CastBase
{
    public function __construct()
    {
        parent::__construct();
    }
}
