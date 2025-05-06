<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTimeString extends CastBase implements CasterInterface
{
    public function __construct(
        public readonly ?string $pattern = null,
        public readonly ?DateTimeFormat $format = DateTimeFormat::ISO,
        public readonly ?string $timezone = null,
    ) {
        parent::__construct([$pattern, $format, $timezone]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$pattern, $format, $timezone] = $args;

        if (!$value instanceof \DateTimeInterface) {
            throw CastingException::castingFailure(static::class, $value, 'Expected a DateTimeInterface instance');
        }

        if (null !== $timezone) {
            $value = $value->setTimezone(new \DateTimeZone($timezone));
        }

        $fmt = $pattern ?? $format?->value ?? 'c';

        return $value->format($fmt);
    }
}
