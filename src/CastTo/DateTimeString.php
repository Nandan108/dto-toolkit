<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Casts a DateTimeInterface object into a formatted string.
 *
 * Note: userland DateTimeInterface implementations without a setTimezone method are not supported.
 *
 * @see https://secure.php.net/manual/en/datetime.format.php
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeString extends DateTime
{
    // Note: This class inherits from DateTime caster to reuse:
    // - constructor arguments ($format and $timezone) and their handling (validation of $format enum)
    // - timezone resolution logic using UsesTimeZoneResolver
    // - bootOnDto(), which calls $this->configureTimezoneResolver();

    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): string
    {
        if (!$value instanceof \DateTimeInterface
            || !method_exists($value, 'setTimezone')
        ) {
            throw TransformException::expected(operand: $value, expected: ['type.date_time']);
        }

        /** @var ?\DateTimeZone $tz */
        $tz = $this->resolveParam('timezone', $value);

        // if the timezone different, we need to adjust it
        if ($tz && $value->getTimezone()->getName() !== $tz->getName()) {
            /** @var \DateTimeInterface */
            $value = $value->setTimezone($tz);
        }

        /** @var string $format */
        [$format] = $args;

        return $value->format($format);
    }
}
