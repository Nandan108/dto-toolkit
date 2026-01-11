<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a numeric string.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsNumericString extends ValidatorBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!is_string($value) || !is_numeric($value)) {
            throw GuardException::failed('must be a numeric string');
        }
    }
}
