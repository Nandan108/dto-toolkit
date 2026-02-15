<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a well-formed email address.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Email extends ValidatorBaseNoArgs
{
    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        if (!is_string($value) || false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'email',
                errorCode: 'guard.email',
            );
        }
    }
}
