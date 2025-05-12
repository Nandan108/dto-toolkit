<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\UsesTimeZoneResolver;

/**
 * Casts a string to a DateTimeImmutable object.
 *
 * @psalm-api
 *
 * @psalm-suppress UnusedClass
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class DateTime extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesTimeZoneResolver;

    public function __construct(
        public readonly string|\BackedEnum $format = DateTimeFormat::ISO_8601,
        public readonly ?string $timezone = null,
    ) {
        $this->throwIfExtensionNotLoaded('intl');

        // if $pattern is an enum, use its value
        if ($format instanceof \BackedEnum) {
            $msg = 'Only string-backed enums are allowed for pattern, got %s::%s with non-string value.';
            is_string($format->value)
                or throw new \InvalidArgumentException(sprintf($msg, $format::class, $format->name));

            $format = $format->value;
        }

        parent::__construct([$format], constructorArgs: ['timezone' => $timezone]);
    }

    /**
     * This function will be called once per caster+ctorArgs+dto.
     */
    #[\Override]
    public function bootOnDto(): void
    {
        $this->configureTimezoneResolver(allowNull: true);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        if (!is_string($value) || '' === trim($value)) {
            throw CastingException::castingFailure(static::class, $value, messageOverride: 'Expected a non-empty date string');
        }

        /** @var string $format */
        [$format] = $args;

        /** @var ?\DateTimeZone $timezone */
        $tz = $this->resolveParam('timezone', $value);

        $dt = \DateTimeImmutable::createFromFormat($format, $value, $tz);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt;
        }

        throw CastingException::castingFailure(static::class, $value, "Unable to parse date with pattern '$format' from '$value'");
    }
}
