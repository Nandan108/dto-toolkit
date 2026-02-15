<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Enum\PresencePolicy;

/**
 * Sets the presence policy at DTO or property level (override).
 *
 * Default: Prop marked as filled only when input is present.
 * NullMeansMissing: When input is present but null, prop NOT marked as filled.
 * MissingMeansDefault: When missing input, prop is marked as filled with default value left intact.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS)]
class Presence
{
    public function __construct(
        public PresencePolicy $policy,
    ) {
    }
}
