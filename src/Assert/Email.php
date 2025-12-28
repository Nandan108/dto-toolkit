<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Email extends ValidatorBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!is_string($value) || false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw GuardException::invalidValue(
                value: $value,
                methodOrClass: self::class,
                template_suffix: 'email.invalid',
                errorCode: 'validate.email.invalid',
            );
        }
    }
}
