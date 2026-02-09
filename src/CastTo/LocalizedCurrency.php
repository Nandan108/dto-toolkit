<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;

/**
 * Formats a numeric value as a localized string using NumberFormatter.
 *
 * Locale can be resolved in 3 ways (in order of precedence):
 *  1. Explicitly via `locale:` parameter (e.g., 'fr_FR')
 *  2. From a `localeProvider:` class (must implement getLocale(): string)
 *  3. From the DTO itself if it has a `getLocale(): string` method
 *  4. From the context if it has a `locale` key
 *
 * Example:
 *   #[CastTo\LocalizedCurrency(locale: \App\Locale\UserLocaleProvider::class)]
 *   public string $localizedAmount;
 *
 * Requires the `intl` extension.
 *
 * @see https://www.php.net/manual/en/class.numberformatter.php
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class LocalizedCurrency extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesLocaleResolver;

    public function __construct(
        string $currency,
        ?string $locale = null,
    ) {
        $this->ensureExtensionLoaded('intl');

        parent::__construct(args: [$currency], constructorArgs: ['locale' => $locale]);
    }

    /**
     * This function will be called once per (caster+ctorArgs)+dto.
     */
    #[\Override]
    public function bootOnDto(): void
    {
        $this->configureLocaleResolver();
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        /** @var string $currency */
        [$currency] = $args;

        if (!is_numeric($value)) {
            throw TransformException::expected(
                operand: $value,
                expected: 'type.numeric',
            );
        }

        /** @var string $locale */
        $locale = $this->resolveParam('locale', $value);

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        // $formatter->formatCurrency() may return false on failure, but the failure is very hard (or impossible)
        // to reproduce since invalid inputs are caught earlier, so we can safely assume
        // that the result is a string.
        /** @var string $formatted */
        $formatted = $formatter->formatCurrency((float) $value, $currency);

        return $formatted;
    }
}
