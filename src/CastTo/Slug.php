<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Traits\UsesDiacriticSanitizer;

/**
 * Converts string into $separator separated groups of lowercase letters.
 **/
/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Slug extends CastBase
{
    use UsesDiacriticSanitizer;

    /** @api */
    public function __construct(string $separator = '-')
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $separator) {
            throw new \InvalidArgumentException('Separator cannot be an empty string.');
        }

        parent::__construct([$separator]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        /** @var non-empty-string $separator */
        [$separator] = $args;

        $value = $this->ensureStringable($value);
        $value = static::removeDiacritics($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', $separator, $value) ?? '';

        return strtolower(trim($value, $separator));
    }
}
