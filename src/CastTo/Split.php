<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Split extends CastBase
{
    /**
     * @param non-empty-string $separator The separator to split the string by (default: ',')
     *
     * @api
     */
    public function __construct(string $separator = ',')
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $separator) {
            throw new \InvalidArgumentException('Separator cannot be an empty string.');
        }

        parent::__construct([$separator]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): array
    {
        /** @var non-empty-string $separator */
        [$separator] = $args;

        $value = $this->ensureStringable($value);

        return explode($separator, $value);
    }
}
