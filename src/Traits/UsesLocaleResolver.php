<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Contracts\LocaleProviderInterface;
use Nandan108\DtoToolkit\Core\BaseDto;

trait UsesLocaleResolver
{
    /** @var \Closure[] */
    protected static array $localeProviders = [];

    /**
     * This resolves the locale provider. It should be called in the constructor.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function resolveLocaleProvider(?string $localeOrProviderClass): \Closure
    {
        /** @psalm-suppress RedundantCondition */
        if (!$this instanceof CastTo) {
            throw new \RuntimeException('Caster must implement CastTo to use UsesLocaleResolver');
        }
        /** @var CastTo|UsesLocaleResolver $this */

        /** @psalm-suppress UnsupportedReferenceUsage */
        $provider = &$this::$localeProviders[$localeOrProviderClass ?? 'default'] ?? null;

        if ($provider) {
            return $provider;
        }

        $isValidLocale = fn (mixed $locale): bool => is_string($locale) && preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale);

        if (null !== $localeOrProviderClass) {
            if (strlen($localeOrProviderClass) <= 5) {
                if (!$isValidLocale($localeOrProviderClass)) {
                    throw new \InvalidArgumentException('Invalid locale "'.$localeOrProviderClass.'"');
                }
                $provider = fn (): string => $localeOrProviderClass;
            } elseif (is_subclass_of($localeOrProviderClass, LocaleProviderInterface::class)) {
                $provider = fn (mixed $value, string $prop, BaseDto $dto): string => forward_static_call([$localeOrProviderClass, 'getLocale'], $value, $prop, $dto);
            } else {
                throw new \InvalidArgumentException('Invalid locale provider class '.$localeOrProviderClass);
            }

            return $provider;
        }

        $provider = static function (mixed $value, string $prop, BaseDto $dto) use ($isValidLocale): string {
            if ($dto instanceof LocaleProviderInterface || method_exists($dto, 'getLocale')) {
                return [$dto, 'getLocale']($value, $prop);
            }

            if ($dto instanceof HasContextInterface && $dto->hasContext('locale')) {
                $locale = $dto->getContext('locale');

                if ($isValidLocale($locale)) {
                    /** @var string $locale */
                    return $locale;
                }

                /** @psalm-suppress RiskyTruthyFalsyComparison */
                $locale = json_encode($locale) ?: '';
                throw new \RuntimeException("Invalid locale $locale in context for ".static::class);
            }

            $noIntlMsg = 'No locale provider was configured and the "intl" extension is not loaded. Cannot resolve locale for '.static::class;
            extension_loaded('intl') or throw new \RuntimeException($noIntlMsg);

            return locale_get_default();
        };

        return $provider;
    }

    /**
     * returns the locale for the given value, locale provider, and context (propName, dto).
     *
     * @throws \RuntimeException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function getLocale(mixed $value, ?string $localeOrProviderClass): string
    {
        /** @var CastTo|UsesLocaleResolver $this */
        $prop = $this::$currentPropName;
        $dto = $this::$currentDto;

        $prop || $dto || throw new \RuntimeException('Current property or DTO is not set');

        $getLocale = $this->resolveLocaleProvider($localeOrProviderClass);

        return $getLocale($value, $prop, $dto);
    }
}
