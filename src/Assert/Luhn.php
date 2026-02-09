<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Support\Luhn as LuhnSupport;

/**
 * Validates a value using the Luhn checksum algorithm.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Luhn extends ValidatorBase
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, true);
        $normalized = LuhnSupport::normalize($value);

        if ('' === $normalized || !ctype_digit($normalized)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'luhn',
                errorCode: 'guard.luhn',
            );
        }

        if (LuhnSupport::check($normalized)) {
            return;
        }

        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'luhn',
            errorCode: 'guard.luhn',
        );
    }
}
