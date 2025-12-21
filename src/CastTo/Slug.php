<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Traits\UsesDiacriticSanitizer;

/**
 * Converts string into $separator separated groups of lowercase letters.
 **/
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Slug extends CastBase
{
    use UsesDiacriticSanitizer;

    public function __construct(string $separator = '-')
    {
        parent::__construct([$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$separator] = $args;

        $value = $this->ensureStringable($value);
        $value = static::removeDiacritics($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', $separator, $value) ?? '';

        return strtolower(trim($value, $separator));
    }
}
