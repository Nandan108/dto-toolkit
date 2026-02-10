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

        ['isBlank' => $isBlank, 'typeToken' => $typeToken] = $this->isBlankValue($value);

        if ($expect !== $isBlank) {
            throw GuardException::expected(
                operand: $value,
                expected: $expect ? 'a blank value' : 'a non-blank value',
                parameters: ['type' => $typeToken],
            );
        }
    }

    /**
     * Determines if the given value is considered blank.
     *
     * @return array{isBlank: bool, typeToken: ?string}
     */
    private function isBlankValue(mixed $value): array
    {
        if (null === $value) {
            return ['isBlank' => true, 'typeToken' => 'type.null'];
        }

        if (\is_string($value)) {
            return '' === trim($value)
                ? ['isBlank' => true, 'typeToken' => 'type.empty_string']
                : ['isBlank' => false, 'typeToken' => 'type.non_empty_string'];
        }

        if (\is_array($value)) {
            return empty($value)
                ? ['isBlank' => true, 'typeToken' => 'type.empty_array']
                : ['isBlank' => false, 'typeToken' => 'type.non_empty_array'];
        }

        if ($value instanceof \Countable) {
            return 0 === $value->count()
                ? ['isBlank' => true, 'typeToken' => 'type.empty_countable']
                : ['isBlank' => false, 'typeToken' => 'type.non_empty_countable'];
        }

        if (\is_iterable($value)) {
            foreach ($value as $_) {
                return ['isBlank' => false, 'typeToken' => 'type.non_empty_iterable'];
            }

            return ['isBlank' => true, 'typeToken' => 'type.empty_iterable'];
        }

        return ['isBlank' => false, 'typeToken' => null];
    }
}
