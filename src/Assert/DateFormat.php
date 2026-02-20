<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a string matches the given date format.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateFormat extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    /** @api */
    public function __construct(string $format)
    {
        if ('' === $format) {
            throw new InvalidArgumentException('DateFormat validator requires a format string.');
        }
        parent::__construct([$format]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var non-empty-string $format */
        $format = $args[0];

        $value = $this->ensureStringable($value, false);

        $dt = \DateTimeImmutable::createFromFormat($format, $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$dt || $errors && ($errors['warning_count'] || $errors['error_count'])) {
            // Date does not match date format
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'date.format_mismatch',
                parameters: ['format' => $format],
                debug: $errors ?: [],
                errorCode: 'guard.date',
            );
        }
    }
}
