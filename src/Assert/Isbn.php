<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates an ISBN (10 or 13).
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Isbn extends ValidatorBase
{
    public const ISBN_10 = 'isbn10';
    public const ISBN_13 = 'isbn13';

    public function __construct(?string $type = null)
    {
        if (null !== $type && !\in_array($type, [self::ISBN_10, self::ISBN_13], true)) {
            throw new InvalidConfigException('Isbn validator: type must be isbn10, isbn13, or null.');
        }

        parent::__construct([$type]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, true);
        $normalized = strtoupper(preg_replace('/[\s-]+/', '', $value) ?? $value);

        /** @var ?string $type */
        $type = $args[0];

        if (self::ISBN_10 !== $type && self::ISBN_13 !== $type) {
            if (self::isValidIsbn10($normalized) || self::isValidIsbn13($normalized)) {
                return;
            }
        } elseif (self::ISBN_10 === $type && self::isValidIsbn10($normalized)) {
            return;
        } elseif (self::ISBN_13 === $type && self::isValidIsbn13($normalized)) {
            return;
        }

        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'isbn.invalid',
            errorCode: 'validate.isbn.invalid',
        );
    }

    private static function isValidIsbn10(string $value): bool
    {
        if (10 !== \strlen($value) || 1 !== preg_match('/^[0-9]{9}[0-9X]$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; ++$i) {
            $sum += (10 - $i) * (int) $value[$i];
        }
        $check = 'X' === $value[9] ? 10 : (int) $value[9];
        $sum += $check;

        return 0 === ($sum % 11);
    }

    private static function isValidIsbn13(string $value): bool
    {
        if (13 !== \strlen($value) || 1 !== preg_match('/^[0-9]{13}$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; ++$i) {
            $digit = (int) $value[$i];
            $sum += (0 === $i % 2) ? $digit : $digit * 3;
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $value[12];
    }
}
