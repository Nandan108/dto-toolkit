<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Casts a DateTimeInterface object into a formatted string.
 *
 * @see https://secure.php.net/manual/en/datetime.format.php
 *
 * @psalm-suppress UnusedClass
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeString extends DateTime
{
    /** @psalm-suppress PossiblyUnusedReturnValue */
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        if (!($value instanceof \DateTimeImmutable || $value instanceof \DateTime)) {
            throw TransformException::expected(static::class, $value, 'DateTime or DateTimeImmutable instance');
        }

        /** @var ?\DateTimeZone $tz */
        $tz = $this->resolveParam('timezone', $value);

        // if the timezone is the same, no need to create a new DateTime object
        if ($tz && $value->getTimezone()->getName() !== $tz->getName()) {
            // rather than mutate the original DateTime object, create a new one
            $value = $value->setTimezone($tz);
        }

        /** @var string $format */
        [$format] = $args;

        return $value->format($format);
    }
}
