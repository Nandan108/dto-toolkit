<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates an IBAN.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Iban extends ValidatorBase
{
    public function __construct(?string $countryCode = null)
    {
        if (null !== $countryCode && 1 !== \preg_match('/^[A-Z]{2}$/i', $countryCode)) {
            throw new InvalidArgumentException('Iban validator: country code must be a 2-letter ISO code.');
        }
        /** @var ?truthy-string $countryCode */
        parent::__construct([$countryCode ? strtoupper($countryCode) : null]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, true);
        $normalized = strtoupper(preg_replace('/\s+/', '', $value) ?? $value);

        $failIf = function (bool $condition) use ($value): void {
            if ($condition) {
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: 'iban',
                    errorCode: 'guard.iban',
                );
            }
        };

        $failIf(\strlen($normalized) < 15 || \strlen($normalized) > 34);

        $failIf(1 !== \preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $normalized));

        /** @var ?string $countryCode */
        $countryCode = $args[0];
        $failIf(null !== $countryCode && !str_starts_with($normalized, $countryCode));

        $failIf(!self::passesChecksum($normalized));
    }

    private static function passesChecksum(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $checksum = 0;

        $length = \strlen($rearranged);
        for ($i = 0; $i < $length; ++$i) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $value = ord($char) - 55;
                $checksum = ($checksum * 100 + $value) % 97;
            } else {
                $checksum = ($checksum * 10 + (int) $char) % 97;
            }
        }

        return 1 === $checksum;
    }
}
