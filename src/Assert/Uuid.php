<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBaseNoArgs;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a UUID.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Uuid extends ValidatorBaseNoArgs
{
    private const REGEX = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        if (!is_string($value) || 1 !== preg_match(self::REGEX, $value)) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'uuid',
                errorCode: 'guard.uuid',
            );
        }
    }
}
