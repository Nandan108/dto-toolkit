<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates whether a value is blank (null/empty/whitespace) based on expectation.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsBlank extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(bool $expect = true)
    {
        parent::__construct([$expect]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$expect] = $args;

        $isBlank = $this->isBlankValue($value);

        if ($expect !== $isBlank) {
            $template = $expect ? 'blank.expected' : 'not_blank';
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: $template,
            );
        }
    }

    private function isBlankValue(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (\is_string($value)) {
            return '' === trim($value);
        }

        if (\is_array($value)) {
            return [] === $value;
        }

        if ($value instanceof \Countable) {
            return 0 === $value->count();
        }

        if (\is_iterable($value)) {
            foreach ($value as $_) {
                return false;
            }

            return true;
        }

        return false;
    }
}
