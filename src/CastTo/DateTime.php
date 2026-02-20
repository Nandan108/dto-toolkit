<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\UsesTimeZoneResolver;

/**
 * Casts a string to a DateTimeImmutable object.
 *
 * @api
 *
 * @psalm-suppress UnusedClass
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class DateTime extends CastBase implements CasterInterface, BootsOnDtoInterface
{
    use UsesTimeZoneResolver;

    /** @api */
    public function __construct(
        public readonly string | \BackedEnum $format = DateTimeFormat::ISO_8601,
        public readonly ?string $timezone = null,
    ) {
        $this->ensureExtensionLoaded('intl');

        // if $pattern is an enum, use its value
        if ($format instanceof \BackedEnum) {
            $msg = 'Only string-backed enums are allowed for pattern, got %s::%s with non-string value.';
            \is_string($format->value)
                || throw new InvalidArgumentException(sprintf($msg, $format::class, $format->name));

            $format = $format->value;
        }

        parent::__construct([$format], constructorArgs: ['timezone' => $timezone]);
    }

    /**
     * This function will be called once per caster+ctorArgs+dto.
     */
    #[\Override]
    /** @internal */
    public function bootOnDto(): void
    {
        $this->configureTimezoneResolver();
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        $value = $this->ensureStringable($value, true);

        /** @var string $format */
        $format = $args[0];

        /** @var ?\DateTimeZone $tz */
        $tz = $this->resolveParam('timezone', $value);

        $dt = \DateTimeImmutable::createFromFormat($format, $value, $tz);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt;
        }

        throw TransformException::reason(
            value: $value,
            template_suffix: 'date.parsing_failed',
            parameters: ['format' => $format],
            errorCode: 'transform.date',
        );
    }
}
