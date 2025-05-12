<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;
use Nandan108\DtoToolkit\Traits\UsesTimeZoneResolver;

/** @psalm-suppress UnusedClass */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeFromLocalized extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesLocaleResolver;
    use UsesTimeZoneResolver;

    public function __construct(
        ?string $locale = null,
        int $dateStyle = \IntlDateFormatter::SHORT,
        int $timeStyle = \IntlDateFormatter::SHORT,
        ?string $pattern = null,
        ?string $timezone = null,
    ) {
        $this->throwIfExtensionNotLoaded('intl');

        parent::__construct(
            args: [$dateStyle, $timeStyle, $pattern],
            constructorArgs: ['locale' => $locale, 'timezone' => $timezone]
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
        $value = $this->throwIfNotStringable($value, 'non-empty date string', true);

        $timezone = $this->resolveParam('timezone', $value);
        $locale = $this->resolveParam('locale', $value);
        /** @var ?\DateTimeZone $timezone */
        /** @var string $locale */
        [$dateStyle, $timeStyle, $pattern] = $args;
        /** @var ?int $dateStyle */
        /** @var ?int $timeStyle */
        /** @var ?string $pattern */
        $formatter = \IntlDateFormatter::create(
            locale: $locale,
            dateType: $dateStyle,
            timeType: $timeStyle,
            timezone: $timezone,
            calendar: \IntlDateFormatter::GREGORIAN,
            pattern: $pattern,
        );

        if (null === $formatter) {
            $message = 'Invalid date formater arguments: '.json_encode(compact('locale', 'dateStyle', 'timeStyle', 'pattern', 'timezone'), JSON_THROW_ON_ERROR);
            throw CastingException::castingFailure(static::class, $value, $message);
        }

        $timestamp = $formatter->parse($value);
        if (false === $timestamp) {
            throw CastingException::castingFailure(static::class, $value, messageOverride: 'IntlDateFormatter failed to parse value: '.$formatter->getErrorMessage());
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
