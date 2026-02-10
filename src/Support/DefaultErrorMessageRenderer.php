<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Support;

use Nandan108\DtoToolkit\Contracts\ErrorMessageRendererInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingExceptionInterface;

final class DefaultErrorMessageRenderer implements ErrorMessageRendererInterface
{
    private static ?string $locale = null;
    /** @var ?\Closure():mixed */
    private static ?\Closure $localeResolver = null;
    /** @var array<string, string> */
    private static array $languageDefaultLocales = [];

    /**
     * Adapter-provided catalogs by locale.
     *
     * @var array<string, list<array{
     *  messages: array<non-empty-string, string>,
     *  tokens: array<non-empty-string, string>,
     *  override: bool
     * }>>
     */
    private static array $registeredCatalogs = [];

    /**
     * @var array<string, array{
     *  messages: array<non-empty-string, string>,
     *  tokens: array<non-empty-string, string>
     * }>
     */
    private static array $resolvedCatalogCache = [];
    /** @var array<non-empty-string, array<non-empty-string, string>> */
    private static array $baseMessagesByLocale = [];
    /** @var array<non-empty-string, array<non-empty-string, string>> */
    private static array $baseTokensByLocale = [];

    #[\Override]
    public function render(ProcessingExceptionInterface $exception): string
    {
        $params = $exception->getMessageParameters();
        $locale = self::resolveLocale();
        $rawTemplate = $exception->getMessageTemplate();
        $catalog = self::resolveCatalog($locale);

        $localTemplate = $catalog['messages'][$rawTemplate] ?? $rawTemplate;
        $replacements = $this->renderPlaceholders($params, $catalog['tokens']);

        $rendered = strtr($localTemplate, $replacements);

        if (!empty($params['propertyPath'])) {
            $rendered = $params['propertyPath'].': '.$rendered;
        }

        return $rendered;
    }

    public static function setLocale(?string $locale): void
    {
        self::$locale = $locale;
    }

    /**
     * @param callable():mixed|null $resolver
     */
    public static function setLocaleResolver(?callable $resolver): void
    {
        self::$localeResolver = null !== $resolver ? \Closure::fromCallable($resolver) : null;
    }

    /**
     * @param non-empty-string      $locale
     * @param array<string, string> $messages
     * @param array<string, string> $tokens
     */
    public static function registerCatalog(
        string $locale,
        array $messages = [],
        array $tokens = [],
        bool $override = true,
    ): void {
        $locale = self::normalizeLocaleId($locale);
        self::$registeredCatalogs[$locale][] = [
            'messages' => self::sanitizeCatalog($messages),
            'tokens'   => self::sanitizeCatalog($tokens),
            'override' => $override,
        ];
        self::clearCatalogCache();
    }

    public static function clearRegisteredCatalogs(): void
    {
        self::$registeredCatalogs = [];
        self::clearCatalogCache();
    }

    /**
     * Sets a default locale to fall back to for a given language code (e.g. 'en' => 'en_GB').
     *
     * @param non-empty-string $locale
     */
    public static function setLanguageDefaultLocale(string $languageCode, string $locale): void
    {
        self::$languageDefaultLocales[strtolower($languageCode)] = self::normalizeLocaleId($locale);
        self::clearCatalogCache();
    }

    /**
     * @param array<non-empty-string, non-empty-string> $map
     */
    public static function setLanguageDefaultLocales(array $map): void
    {
        self::$languageDefaultLocales = [];
        foreach ($map as $languageCode => $locale) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (is_string($languageCode) && is_string($locale) && $languageCode && $locale) {
                self::$languageDefaultLocales[strtolower($languageCode)] = self::normalizeLocaleId($locale);
            }
        }
        self::clearCatalogCache();
    }

    public static function clearLanguageDefaultLocales(): void
    {
        self::$languageDefaultLocales = [];
        self::clearCatalogCache();
    }

    /**
     * Clears resolved catalog cache.
     *
     * Set $reloadBaseCatalogs to true to also clear the base file cache
     * loaded from resources/i18n/{locale}.
     */
    public static function clearCatalogCache(bool $reloadBaseCatalogs = false): void
    {
        self::$resolvedCatalogCache = [];
        if ($reloadBaseCatalogs) {
            self::$baseMessagesByLocale = [];
            self::$baseTokensByLocale = [];
        }
    }

    public static function resetRuntimeConfig(): void
    {
        self::$locale = null;
        self::$localeResolver = null;
        self::clearRegisteredCatalogs();
        self::clearLanguageDefaultLocales();
    }

    /** @param array<string, mixed> $params */
    private function renderPlaceholders(array $params, array $tokens): array
    {
        $replacements = [];

        foreach ($params as $key => $value) {
            $replacements[':'.$key] = $this->renderParameter($key, $value, $params, $tokens);
        }

        return $replacements;
    }

    /** @param array<string, mixed> $allParams */
    private function renderParameter(string $key, mixed $value, array $allParams, array $tokens): string
    {
        if (('expected' === $key || 'type' === $key) && (is_string($value) || is_array($value))) {
            $list = is_array($value) ? $value : [$value];
            $items = [];
            foreach ($list as $token) {
                $items[] = $this->renderToken(
                    is_string($token)
                        ? $this->normalizeTypeToken($token, $tokens)
                        : $this->stringify($token),
                    $allParams,
                    $tokens,
                );
            }

            return $this->joinHuman($items);
        }

        if (is_string($value)) {
            return $this->renderToken($value, $allParams, $tokens);
        }

        if (is_array($value)) {
            /** @var list<mixed> $value */
            $items = array_map(fn (mixed $v): string => $this->stringify($v), $value);

            return $this->joinHuman($items);
        }

        return $this->stringify($value);
    }

    /** @param array<string, mixed> $allParams */
    private function renderToken(string $value, array $allParams, array $tokens): string
    {
        $tokenTemplate = $tokens[$value] ?? null;
        if (!is_string($tokenTemplate)) {
            return $value;
        }

        $map = [];
        foreach ($allParams as $key => $paramValue) {
            $map[':'.$key] = $this->stringify($paramValue);
        }

        return strtr($tokenTemplate, $map);
    }

    private function normalizeTypeToken(string $token, array $tokens): string
    {
        if (isset($tokens[$token])) {
            return $token;
        }

        $prefixed = 'type.'.$token;

        return isset($tokens[$prefixed]) ? $prefixed : $token;
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (null === $value) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return false === $json ? get_debug_type($value) : $json;
    }

    /** @param list<string> $items */
    private function joinHuman(array $items): string
    {
        $items = array_values(array_filter($items, static fn (string $s): bool => '' !== trim($s)));
        $count = count($items);
        if (0 === $count) {
            return '';
        }
        if (1 === $count) {
            return $items[0];
        }
        if (2 === $count) {
            return $items[0].' or '.$items[1];
        }

        /** @var non-empty-string $tail */
        $tail = array_pop($items);

        return implode(', ', $items).', or '.$tail;
    }

    /**
     * Resolves the locale to use for rendering messages.
     *
     * @return non-empty-string
     */
    private static function resolveLocale(): string
    {
        $resolved = self::$locale;

        if (!is_string($resolved) && null !== self::$localeResolver) {
            $candidate = (self::$localeResolver)();
            $resolved = is_string($candidate) ? $candidate : null;
        }

        if (!is_string($resolved) && extension_loaded('intl')) {
            $resolved = locale_get_default();
        }

        /** @var non-empty-string */
        return is_string($resolved) && '' !== trim($resolved) ? $resolved : 'en';
    }

    /**
     * @return list<non-empty-string>
     */
    private static function expandLocaleFallbacks(string $locale): array
    {
        // Normalize separators
        $locale = str_replace('-', '_', $locale);

        // Extract language + region (ignore variants for now)
        if (preg_match('/^([a-z]{2,3})(?:_([A-Z]{2}))?/i', $locale, $m)) {
            $lang = strtolower($m[1]);
            /** @var ?truthy-string $region */
            $region = isset($m[2]) ? strtoupper($m[2]) : null;
            $requested = $region ? "{$lang}_{$region}" : $lang;

            $fallbacks = [$requested, $lang];

            // Preferred per-language default locale (adapter/project configurable)
            $mapped = self::$languageDefaultLocales[$lang] ?? null;
            if (is_string($mapped)
                && '' !== trim($mapped)
                && str_starts_with(strtolower($mapped), $lang.'_')
                && !in_array($mapped, $fallbacks, true)
            ) {
                $fallbacks[] = $mapped;
            }

            // Deterministic first same-language regional locale, if any.
            $sameLangRegionals = array_values(array_filter(
                self::availableLocales(),
                static fn (string $candidate): bool => 1 === preg_match('/^'.preg_quote($lang, '/').'_[A-Z]{2}$/', $candidate),
            ));
            sort($sameLangRegionals, SORT_STRING);
            foreach ($sameLangRegionals as $candidate) {
                if (!in_array($candidate, $fallbacks, true)) {
                    $fallbacks[] = $candidate;
                    break;
                }
            }

            if (!in_array('en', $fallbacks, true)) {
                $fallbacks[] = 'en';
            }

            /** @var list<non-empty-string> */
            return array_values(array_filter(array_unique($fallbacks)));
        }

        // If parsing fails, hard fallback
        return ['en'];
    }

    /**
     * Loads and merges catalogs for the given locale, applying locale fallbacks as needed.
     *
     * @param non-empty-string $locale
     *
     * @return array{messages: array<non-empty-string, string>, tokens: array<non-empty-string, string>}
     */
    private static function resolveCatalog(string $locale): array
    {
        $cacheKey = strtolower(self::normalizeLocaleId($locale));

        if (!isset(self::$resolvedCatalogCache[$cacheKey])) {
            $messages = $tokens = [];
            $fallbacks = self::expandLocaleFallbacks($locale);

            // Merge from generic to specific so specific locale overrides generic ones.
            foreach (array_reverse($fallbacks) as $candidate) {
                $exact = self::catalogForLocale($candidate);
                $messages = array_replace($messages, $exact['messages']);
                $tokens = array_replace($tokens, $exact['tokens']);
            }

            self::$resolvedCatalogCache[$cacheKey] = [
                'messages' => $messages,
                'tokens'   => $tokens,
            ];
        }

        return self::$resolvedCatalogCache[$cacheKey];
    }

    /**
     * Loads the base catalog for a specific locale from resources/i18n, and merges any registered catalogs for that locale.
     *
     * @param non-empty-string $locale
     *
     * @return array{messages: array<non-empty-string, string>, tokens: array<non-empty-string, string>}
     */
    private static function catalogForLocale(string $locale): array
    {
        $locale = self::normalizeLocaleId($locale);
        /** @param non-empty-string $locale */
        $messages = self::loadBaseCatalog($locale, 'messages.php');
        $tokens = self::loadBaseCatalog($locale, 'tokens.php');

        foreach (self::$registeredCatalogs[$locale] ?? [] as $registration) {
            if ($registration['override']) {
                $messages = array_replace($messages, $registration['messages']);
                $tokens = array_replace($tokens, $registration['tokens']);
                continue;
            }

            $messages += $registration['messages'];
            $tokens += $registration['tokens'];
        }

        return [
            'messages' => $messages,
            'tokens'   => $tokens,
        ];
    }

    /** @return list<string> */
    private static function availableLocales(): array
    {
        $locales = array_keys(self::$registeredCatalogs);
        $resourceRoot = dirname(__DIR__, 2).'/resources/i18n';
        if (is_dir($resourceRoot)) {
            $entries = scandir($resourceRoot);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if ('.' === $entry || '..' === $entry || '' === $entry) {
                        continue;
                    }
                    if (is_dir($resourceRoot.'/'.$entry)) {
                        $locales[] = self::normalizeLocaleId($entry);
                    }
                }
            }
        }

        $locales = array_values(array_unique($locales, SORT_REGULAR));
        sort($locales, SORT_STRING);

        /** @var list<string> $locales */
        return $locales;
    }

    /**
     * @param non-empty-string $locale
     *
     * @return array<non-empty-string, string>
     * */
    private static function loadBaseCatalog(string $locale, string $file): array
    {
        $cache = 'messages.php' === $file ? self::$baseMessagesByLocale : self::$baseTokensByLocale;
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $path = dirname(__DIR__, 2).'/resources/i18n/'.$locale.'/'.$file;
        if (!is_file($path)) {
            $catalog = [];
        } else {
            $data = require $path;
            $catalog = is_array($data) ? self::sanitizeCatalog($data) : [];
        }

        if ('messages.php' === $file) {
            self::$baseMessagesByLocale[$locale] = $catalog;
        } else {
            self::$baseTokensByLocale[$locale] = $catalog;
        }

        return $catalog;
    }

    /**
     * Sanitizes a catalog by filtering out entries with non-string or empty keys, and non-string values.
     *
     * @param array<mixed, mixed> $data
     *
     * @return array<non-empty-string, string>
     */
    private static function sanitizeCatalog(array $data): array
    {
        /** @var array<non-empty-string, string> $catalog */
        return array_filter(
            $data,
            static fn (mixed $v, mixed $k): bool => is_string($k) && '' !== trim($k) && is_string($v),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Normalizes a locale ID by trimming whitespace and replacing dashes with underscores.
     *
     * @param non-empty-string $locale
     *
     * @return non-empty-string
     */
    private static function normalizeLocaleId(string $locale): string
    {
        /** @var non-empty-string */
        return str_replace('-', '_', trim($locale));
    }
}
