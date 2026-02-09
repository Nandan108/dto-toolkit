<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates an ISO 4217 currency code.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Currency extends ValidatorBase
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = strtoupper($this->ensureStringable($value, true));

        if (1 === \preg_match('/^[A-Z]{3}$/', $value)) {
            return;
        }

        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'currency',
            errorCode: 'guard.currency',
        );
    }
}
