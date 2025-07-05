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
final class LocalizedDateTime extends CastBase implements CasterInterface, BootsOnDtoInterface
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

        parent::__construct([$dateStyle, $timeStyle, $pattern], ['locale' => $locale, 'timezone' => $timezone]);
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
    public function cast(mixed $value, array $args): string
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: int, 1: int, 2:?string} $args */
        [$dateStyle, $timeStyle, $pattern] = $args;

        if (!$value instanceof \DateTimeInterface) {
            throw CastingException::castingFailure(static::class, $value, 'Value must be a DateTimeInterface');
        }

        /** @var string $locale */
        $locale = $this->resolveParam('locale', $value);
        /** @var ?\DateTimeZone $timezone */
        $timezone = $this->resolveParam('timezone', $value);

        $formatter = \IntlDateFormatter::create(
            locale: $locale,
            dateType: $dateStyle,
            timeType: $timeStyle,
            timezone: $timezone,
            calendar: \IntlDateFormatter::GREGORIAN,
            pattern: $pattern,
        );
        if (!$formatter) {
            $message = 'Invalid date formater arguments: '.json_encode(compact('locale', 'dateStyle', 'timeStyle', 'pattern', 'timezone'), JSON_THROW_ON_ERROR);
            throw CastingException::castingFailure(static::class, $value, messageOverride: $message);
        }

        $formatted = $formatter->format($value);

        return $formatted;
    }
}
