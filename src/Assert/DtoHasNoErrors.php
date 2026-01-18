<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Exception\Process\InnerDtoErrorsException;

/**
 * Validates that a nested DTO has no processing errors.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DtoHasNoErrors extends ValidatorBase
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if (!$value instanceof BaseDto) {
            throw GuardException::expected(
                methodOrClass: self::class,
                operand: $value,
                expected: BaseDto::class,
            );
        }

        $errorList = $value->getErrorList();
        if (!$errorList->isEmpty()) {
            throw InnerDtoErrorsException::fromList($errorList);
        }
    }
}
