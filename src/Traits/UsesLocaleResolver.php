<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;

/** @api */
trait UsesLocaleResolver
{
    use UsesParamResolver;

    public function configureLocaleResolver(?string $localeOrProvider = null, string $paramName = 'locale'): void
    {
        /** @psalm-suppress UndefinedDocblockClass */
        /** @var CastTo|UsesParamResolver $this */
        $this->configureParamResolver(
            paramName: $paramName,
            valueOrProvider: $localeOrProvider ?? $this->constructorArgs[$paramName] ?? null,
            checkValid: fn (mixed $locale): bool => \is_string($locale)
                    && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?(\.[\w\-]+)?(@[\w\-]+)?$/i', $locale),
            fallback: fn (): string =>  locale_get_default(), // ICU default locale
        );
    }
}
