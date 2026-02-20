<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Enum;

/** @api */
enum ConstructMode
{
    /**
     * Instantiate the entity and hydrate via prop-access.
     */
    case Default;

    /**
     * Pass the full outbound property map as a single array to the constructor.
     * The constructor is then responsible for entity hydration, and how to handle extraneous or missing keys.
     */
    case Array;

    /**
     * Pass outbound properties as named arguments.
     * Requires constructor parameter names to match outbound property names (after MapTo).
     */
    case NamedArgs;
}
