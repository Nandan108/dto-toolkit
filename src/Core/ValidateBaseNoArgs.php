<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

/**
 * Base class for Casters that take no arguments and don't need a constructor.
 * This is used so IDEs don't auto add $args and $constructorArgs parameters.
 */
abstract class ValidateBaseNoArgs extends ValidateBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct()
    {
        parent::__construct();
    }
}
