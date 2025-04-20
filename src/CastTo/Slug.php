<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Slug extends CastBase implements CasterInterface
{
    public function __construct(string $separator = '-', bool $outbound = false)
    {
        parent::__construct($outbound, [$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        [$separator] = $args;

        $value = $this->throwIfNotStringable($value);

        $this->checkIntlAvailable();

        /** @psalm-suppress PossiblyNullReference */
        $value = \Transliterator::create('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove')->transliterate($value);

        /** @psalm-suppress PossiblyNullArgument */
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', $separator, $value), $separator));
    }

    protected function checkIntlAvailable(): void
    {
        if (!class_exists(\Transliterator::class)) {
            throw new CastingException('Slug caster requires the intl extension for transliteration.');
        }
    }
}
