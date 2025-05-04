<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\UsesLocaleResolver;

/** @psalm-suppress UnusedClass */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class LocalizedDateTime extends CastBase implements CasterInterface
{
    use UsesLocaleResolver;

    public function __construct(
        ?string $locale = null,
        int $dateStyle = \IntlDateFormatter::SHORT,
        int $timeStyle = \IntlDateFormatter::SHORT,
        ?string $pattern = null,
        ?string $timezone = null,
    ) {
        $this->resolveLocaleProvider($locale);

        parent::__construct([$locale, $dateStyle, $timeStyle, $pattern, $timezone]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$localeOrProviderClass, $dateStyle, $timeStyle, $pattern, $timezone] = $args;

        if (!$value instanceof \DateTimeInterface) {
            throw CastingException::castingFailure(static::class, $value, 'Value must be a DateTimeInterface');
        }

        $locale = $this->getLocale($value, $localeOrProviderClass);

        try {
            $formatter = new \IntlDateFormatter(
                $locale,
                $dateStyle,
                $timeStyle,
                $timezone ?? $value->getTimezone()->getName(),
                \IntlDateFormatter::GREGORIAN,
                $pattern ?: null
            );
        } catch (\IntlException $e) {
            $message = 'Invalid date formater arguments: '.json_encode(compact('locale', 'dateStyle', 'timeStyle', 'pattern', 'timezone'), JSON_THROW_ON_ERROR);
            throw CastingException::castingFailure(static::class, $value, $message.' - '.$e->getMessage());
        }

        // $formatter->format() may return false on failure, but the failure is impossible
        // to reproduce since invalid inputs are caught earlier, so we can safely assume
        // that the result is a string.
        /** @var string $formatted */
        $formatted = $formatter->format($value);

        return $formatted;
    }
}
