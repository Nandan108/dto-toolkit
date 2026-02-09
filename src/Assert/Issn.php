<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates an ISSN.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Issn extends ValidatorBase
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, true);
        $normalized = strtoupper(preg_replace('/[\s-]+/', '', $value) ?? $value);

        if (!self::isValidIssn($normalized)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'issn',
                errorCode: 'guard.issn',
            );
        }
    }

    private static function isValidIssn(string $value): bool
    {
        if (8 !== \strlen($value) || 1 !== preg_match('/^[0-9]{7}[0-9X]$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 7; ++$i) {
            $sum += (8 - $i) * (int) $value[$i];
        }

        $remainder = $sum % 11;
        $check = 11 - $remainder;
        $expected = match ($check) {
            10      => 'X',
            11      => '0',
            default => (string) $check,
        };

        return $value[7] === $expected;
    }
}
