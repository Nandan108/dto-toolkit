<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Traits\UsesDiacriticSanitizer;

/**
 * Converts string into $separator separated groups of lowercase letters.
 *
 * @psalm-api
 **/
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class RemoveDiacritics extends CastBase
{
    use UsesDiacriticSanitizer;

    public function __construct(?bool $useIntlExtension = null)
    {
        parent::__construct([$useIntlExtension]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$useIntlExtension] = $args;

        return static::removeDiacritics($this->throwIfNotStringable($value), $useIntlExtension);
    }
}
