<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/**
 * Replaces a value with another if it is found in a given list of values.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class ReplaceWhen extends CastBase
{
    /**
     * @param mixed|array<mixed> $when   The value(s) to match against. Can be a single value or an array of values.
     * @param mixed              $then   the value to replace with if a match is found (default: null)
     * @param bool               $strict Whether to use strict comparison (===) when
     *
     * @api
     */
    public function __construct(mixed $when, mixed $then = null, bool $strict = true)
    {
        if (!\is_array($when)) {
            $when = [$when];
        }

        parent::__construct([$when, $then, $strict]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        /** @var list<mixed> */
        $when = $args[0];
        /** @psalm-var mixed $then */
        $then = $args[1];
        /** @var bool $strict */
        $strict = $args[2];

        $match = in_array($value, $when, $strict);

        return $match ? $then : $value;
    }
}
