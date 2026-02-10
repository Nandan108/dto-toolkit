<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Computes a time difference from an ISO datetime string, in seconds/hours/days/years.
 *
 * Accepts values as either DateTimeInterface instances or ISO datetime strings,
 * and computes the difference from a reference datetime (defaulting to now in UTC).
 *
 * @template TUnit of 'seconds'|'hours'|'days'|'years'
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Age extends CastBase
{
    private const SECONDS_PER_HOUR = 3600.0;
    private const SECONDS_PER_DAY = 86400.0;
    private const SECONDS_PER_YEAR = 31536000.0; // 365 days

    private readonly ?\DateTimeImmutable $relativeTo;

    /**
     * @param TUnit   $in
     * @param ?string $relativeTo ISO datetime string (timezone allowed), defaults to now in UTC
     */
    public function __construct(string $in = 'years', ?string $relativeTo = null)
    {
        $this->relativeTo = null !== $relativeTo
            ? $this->parseIsoDateTime($relativeTo, 'relativeTo', true)
            : null;

        if (!in_array($in, ['seconds', 'hours', 'days', 'years'], true)) {
            throw new InvalidArgumentException("Age caster: invalid unit '{$in}'.");
        }

        parent::__construct(args: [$in], constructorArgs: ['relativeTo' => $relativeTo]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): float
    {
        if ($value instanceof \DateTimeInterface) {
            $date = $value;
        } else {
            $value = $this->ensureStringable($value, false);
            $valueString = $this->ensureStringable($value, true);
            $date = $this->parseIsoDateTime($valueString, 'value', false);
        }

        $relativeTo = $this->relativeTo ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $diffSeconds = $relativeTo->getTimestamp() - $date->getTimestamp();

        /** @var TUnit $unit */
        [$unit] = $args;

        $divisor = match ($unit) {
            'seconds' => 1.0,
            'hours'   => self::SECONDS_PER_HOUR,
            'days'    => self::SECONDS_PER_DAY,
            'years'   => self::SECONDS_PER_YEAR,
        };

        return (float) $diffSeconds / $divisor;
    }

    private function parseIsoDateTime(string $value, string $label, bool $isConfig): \DateTimeImmutable
    {
        $timezone = new \DateTimeZone('UTC');
        $formats = [
            DateTimeFormat::RFC3339_EXTENDED->value,
            DateTimeFormat::ISO_8601->value,
            DateTimeFormat::ISO_NO_TZ->value,
        ];

        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $value, $timezone);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }
        }

        if ($isConfig) {
            throw new InvalidArgumentException("Age caster: invalid {$label} ISO datetime string '{$value}'.");
        }

        throw TransformException::reason(
            value: $value,
            template_suffix: 'date.parsing_failed',
            parameters: ['format' => 'ISO 8601'],
            errorCode: 'transform.date',
        );
    }
}
