<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTime extends CastBase implements CasterInterface
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public function __construct(
        public readonly ?string $pattern = null,
        public readonly ?DateTimeFormat $format = DateTimeFormat::ISO,
        public readonly ?string $timezone = null,
    ) {
        parent::__construct([$pattern, $format, $timezone]);
        // noop to keep psalm happy
        [$this->pattern, $this->format, $this->timezone];
    }

    #[\Override]
    public function cast(mixed $value, array $args): \DateTimeInterface
    {
        [$pattern, $format, $timezone] = $args;

        if (!is_string($value) || '' === trim($value)) {
            throw CastingException::castingFailure(static::class, $value, messageOverride: 'Expected non-empty date string');
        }

        $tz = new \DateTimeZone($timezone ?? date_default_timezone_get());
        $fmt = $pattern ?? $format?->value ?? 'c';

        $dt = \DateTimeImmutable::createFromFormat($fmt, $value, $tz);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt;
        }

        throw CastingException::castingFailure(static::class, $value, "Unable to parse date with pattern '$fmt' from '$value'");
    }
}
