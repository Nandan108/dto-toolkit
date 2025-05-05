<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeFromLocalized extends CastBase implements CasterInterface
{
    use UsesLocaleResolver;

    public function __construct(
        ?string $locale = null,
        int $dateStyle = \IntlDateFormatter::SHORT,
        int $timeStyle = \IntlDateFormatter::SHORT,
        ?string $pattern = null,
        ?string $timezone = null,
    ) {
        extension_loaded('intl') || throw new \RuntimeException('Intl extension is required for FromLocalizedDateTime');

        $this->resolveLocaleProvider($locale);

        parent::__construct([$locale, $dateStyle, $timeStyle, $pattern, $timezone]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): \DateTimeInterface
    {
        [$localeOrProviderClass, $dateStyle, $timeStyle, $pattern, $timezone] = $args;

        $value = trim($this->throwIfNotStringable($value));
        if ('' === $value) {
            throw CastingException::castingFailure(static::class, $value, 'Expected non-empty string for date/time parsing');
        }

        $locale = $this->getLocale($value, $localeOrProviderClass);

        $timezone ??= date_default_timezone_get();

        // Try IntlDateFormatter first (if intl is available and pattern provided or locale suggests use)
        $formatter = \IntlDateFormatter::create(
            $locale,
            $dateStyle,
            $timeStyle,
            $timezone,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $timestamp = $formatter->parse($value);

        if (false === $timestamp) {
            throw CastingException::castingFailure(static::class, $value, 'IntlDateFormatter failed to parse value: '.$formatter->getErrorMessage());
        }

        return (new \DateTimeImmutable('@'.$timestamp))->setTimezone(new \DateTimeZone($timezone));
    }
}
