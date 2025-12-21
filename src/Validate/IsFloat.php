<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsFloat extends ValidateBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (is_float($value)) {
            return;
        }

        if (is_string($value) && false !== filter_var($value, FILTER_VALIDATE_FLOAT)) {
            return;
        }

        throw GuardException::failed('must be a float');
    }
}
