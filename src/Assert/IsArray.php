<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsArray extends ValidatorBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!\is_array($value)) {
            throw GuardException::failed('must be an array');
        }
    }
}
