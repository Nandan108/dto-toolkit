<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;

/**
 * Formats a numeric value as a localized string using NumberFormatter.
 *
 * Locale can be resolved in 3 ways (in order of precedence):
 *  1. Explicitly via `locale:` parameter (e.g., 'fr_FR')
 *  2. From a `localeProvider:` class (must implement getLocale(): string)
 *  3. From the DTO itself if it has a `getLocale(): string` method
 *
 * Example:
 *   #[CastTo\LocalizedNumber(localeProvider: \App\Locale\UserLocaleProvider::class)]
 *   public string $localizedAmount;
 *
 * Requires the `intl` extension.
 *
 * @see https://www.php.net/manual/en/class.numberformatter.php
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class LocalizedNumber extends CastBase implements CasterInterface
{
    use UsesLocaleResolver;

    public function __construct(
        int $style = \NumberFormatter::DECIMAL,
        int $precision = 2,
        ?string $locale = null,
    ) {
        $this->resolveLocaleProvider($locale);

        if ($precision < 0) {
            throw new \InvalidArgumentException('Precision must be a non-negative integer.');
        }

        parent::__construct([$locale, $style, $precision]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$localeOrProviderClass, $style, $fractionDigits] = $args;

        if (!is_numeric($value)) {
            throw CastingException::castingFailure(static::class, $value, 'Value is not numeric.');
        }

        $locale = $this->getLocale($value, $localeOrProviderClass);

        $formatter = new \NumberFormatter($locale, $style);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);

        // $formatter->format() may return false on failure, but the failure is very hard
        // to reproduce since invalid inputs are caught earlier, so we can safely assume
        // that the result is a string.
        /** @var string $formatted */
        $formatted = $formatter->format((float) $value);

        return $formatted;
    }
}
