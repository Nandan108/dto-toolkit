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
/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class LocalizedDateTime extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesLocaleResolver, UsesTimeZoneResolver {
        // Both resolver traits import UsesParamResolver; keep a single canonical implementation.
        UsesLocaleResolver::configureParamResolver insteadof UsesTimeZoneResolver;
        UsesLocaleResolver::getParamResolverConfig insteadof UsesTimeZoneResolver;
        UsesLocaleResolver::resolveParam insteadof UsesTimeZoneResolver;
        UsesLocaleResolver::resolveParamProvider insteadof UsesTimeZoneResolver;
    }

    /** @api */
    public function __construct(
        ?string $locale = null,
        int $dateStyle = \IntlDateFormatter::SHORT,
        int $timeStyle = \IntlDateFormatter::SHORT,
        ?string $pattern = null,
        ?string $timezone = null,
    ) {
        $this->ensureExtensionLoaded('intl');

        parent::__construct([$dateStyle, $timeStyle, $pattern], ['locale' => $locale, 'timezone' => $timezone]);
    }

    /**
     * This function will be called once per caster+ctorArgs+dto.
     */
    #[\Override]
    /** @internal */
    public function bootOnDto(): void
    {
        $this->configureLocaleResolver();
        $this->configureTimeZoneResolver();
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: int, 1: int, 2:?string} $args */
        [$dateStyle, $timeStyle, $pattern] = $args;

        if (!$value instanceof \DateTimeInterface) {
            // TODO: check whether to throw an InvalidConfigException instead
            // Because $value should have been normalized properly, but isn't.
            throw TransformException::expected($value, 'type.date_time_interface');
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

        if (null === $formatter) {
            throw new InvalidConfigException(
                message: 'Failed to create IntlDateFormatter instance.',
                debug: [
                    'locale'    => $locale,
                    'dateStyle' => $dateStyle,
                    'timeStyle' => $timeStyle,
                    'pattern'   => $pattern,
                    'timezone'  => $timezone?->getName(),
                ],
            );
        }

        $formatted = $formatter->format($value);

        return $formatted;
    }
}
