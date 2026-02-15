<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Split extends CastBase
{
    /** @api */
    public function __construct(string $separator = ',')
    {
        parent::__construct([$separator]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): array
    {
        [$separator] = $args;

        $value = $this->ensureStringable($value);

        return explode($separator, $value);
    }
}
