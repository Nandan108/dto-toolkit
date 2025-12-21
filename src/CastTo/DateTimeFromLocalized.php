<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;
use Nandan108\DtoToolkit\Traits\UsesTimeZoneResolver;

/** @psalm-suppress UnusedClass */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeFromLocalized extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesLocaleResolver;
    use UsesTimeZoneResolver;

    /**
     * Caster to transform a localized date string into a DateTimeImmutable object.
     *
     * @param mixed $locale    Locale string (e.g. 'en_US', 'fr_FR'). If null, resolution via dto method or context is attempted.
     * @param int   $dateStyle Date style constant (IntlDateFormatter::NONE, ::SHORT, ::MEDIUM, ::LONG, ::FULL)
     * @param int   $timeStyle Time style constant (IntlDateFormatter::NONE, ::SHORT, ::MEDIUM, ::LONG, ::FULL)
     * @param mixed $pattern   Custom pattern (overrides $dateStyle and $timeStyle). For syntax, see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     * @param mixed $timezone
     */
    public function __construct(
        ?string $locale = null,
        int $dateStyle = \IntlDateFormatter::SHORT,
        int $timeStyle = \IntlDateFormatter::SHORT,
        ?string $pattern = null,
        ?string $timezone = null,
    ) {
        $this->ensureExtensionLoaded('intl');

        parent::__construct(
            args: [$dateStyle, $timeStyle, $pattern],
            constructorArgs: ['locale' => $locale, 'timezone' => $timezone],
        );
    }

    /**
     * This function will be called once per caster+ctorArgs+dto.
     */
    #[\Override]
    public function bootOnDto(): void
    {
        $this->configureLocaleResolver();
        $this->configureTimeZoneResolver();
    }

    #[\Override]
    public function cast(mixed $value, array $args): \DateTimeInterface
    {
        $value = $this->ensureStringable($value, true);

        /** @var ?\DateTimeZone $timezone */
        $timezone = $this->resolveParam('timezone', $value);
        /** @var string $locale */
        $locale = $this->resolveParam('locale', $value);

        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: int, 1: int, 2: ?string} $args */
        [$dateStyle, $timeStyle, $pattern] = $args;

        $formatter = \IntlDateFormatter::create(
            locale: $locale,
            dateType: $dateStyle,
            timeType: $timeStyle,
            timezone: $timezone,
            calendar: \IntlDateFormatter::GREGORIAN,
            pattern: $pattern,
        );

        $getParams = fn (): array => [
            'locale'    => $locale,
            'dateStyle' => $dateStyle,
            'timeStyle' => $timeStyle,
            'pattern'   => $pattern,
            'timezone'  => $timezone?->getName(),
        ];

        if (null === $formatter) {
            throw new InvalidConfigException(
                message: 'Failed to create IntlDateFormatter instance.',
                debug: $getParams(),
            );
        }

        $timestamp = $formatter->parse($value);

        if (false === $timestamp) {
            throw TransformException::reason(
                methodOrClass: static::class,
                value: $value,
                template_suffix: 'date.parsing_failed',
                parameters: $getParams(),
            );
        }

        /** @var \DateTimeImmutable $dateTime */
        $dateTime = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);

        // Set the timezone if provided
        if ($timezone) {
            $dateTime = $dateTime->setTimezone($timezone);
        }

        return $dateTime;
    }
}
