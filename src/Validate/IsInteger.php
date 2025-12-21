<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsInteger extends ValidateBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (is_int($value)) {
            return;
        }

        if (is_float($value) && 0.0 === fmod($value, 1.0)) {
            return;
        }

        if (is_string($value) && false !== filter_var($value, FILTER_VALIDATE_INT)) {
            return;
        }

        throw GuardException::failed('must be an integer');
    }
}
