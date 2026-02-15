<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Enum;

/** @api */
enum PresencePolicy: string
{
    /**
     * Prop marked as filled only when input is present.
     */
    case Default = 'default';

    /**
     * When input is present but null, prop NOT marked as filled.
     */
    case NullMeansMissing = 'null_means_missing';

    /**
     * When missing input, prop is marked as filled with default value left intact.
     */
    case MissingMeansDefault = 'missing_means_default';
}
