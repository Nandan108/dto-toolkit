<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class NotNull extends ValidatorBaseNoArgs
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (null === $value) {
            throw GuardException::required(
                what: 'non_null_value',
                badValue: $value,
                methodOrClass: __CLASS__,
            );
        }
    }
}
