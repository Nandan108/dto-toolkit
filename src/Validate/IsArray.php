<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsArray extends ValidateBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!\is_array($value)) {
            throw GuardException::failed('must be an array');
        }
    }
}
