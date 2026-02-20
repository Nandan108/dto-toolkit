<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;

/** @api */
trait UsesLocaleResolver
{
    use UsesParamResolver;

    public function configureLocaleResolver(?string $localeOrProvider = null, string $paramName = 'locale'): void
    {
        /** @var string|callable|null $valueOrProvider */
        $valueOrProvider = $localeOrProvider;
        if (null === $valueOrProvider) {
            /** @psalm-suppress MixedAssignment */
            $valueOrProvider = $this->getConstructorArg($paramName);
            if (null !== $valueOrProvider && !\is_string($valueOrProvider) && !\is_callable($valueOrProvider)) {
                throw new InvalidConfigException(
                    "Parameter '$paramName' constructorArg must resolve to string|callable|null, got ".get_debug_type($valueOrProvider),
                );
            }
        }

        /** @psalm-suppress UndefinedDocblockClass */
        /** @var CastTo|UsesParamResolver $this */
        $this->configureParamResolver(
            paramName: $paramName,
            valueOrProvider: $valueOrProvider,
            checkValid: fn (mixed $locale): bool => \is_string($locale)
                    && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?(\.[\w\-]+)?(@[\w\-]+)?$/i', $locale),
            fallback: fn (): string =>  locale_get_default(), // ICU default locale
        );
    }
}
