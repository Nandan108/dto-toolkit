<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
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
final class LocalizedNumber extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesLocaleResolver;

    public function __construct(
        int $style = \NumberFormatter::DECIMAL,
        int $precision = 2,
        ?string $locale = null,
    ) {
        $this->throwIfExtensionNotLoaded('intl');
        if ($precision < 0) {
            throw new \InvalidArgumentException('Precision must be a non-negative integer.');
        }

        // locale goes to constructorArgs because it needs to be part of the caster's instance cache key
        parent::__construct(args: [$style, $precision], constructorArgs: ['locale' => $locale]);
    }

    /**
     * This function will be called once per caster+ctorArgs+dto.
     */
    #[\Override]
    public function bootOnDto(): void
    {
        $this->configureLocaleResolver();
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$style, $fractionDigits] = $args;

        $this->throwIfNotNumeric($value);

        $locale = $this->resolveParam('locale', $value);

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
