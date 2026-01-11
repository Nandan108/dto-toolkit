<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Assert\Support\SequenceMatcher;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a string or sequence value contains the given needle of same type (string or iterable).
 * The optional $at position can be:
 * - null: anywhere
 * - 'start' or 'end': anchored match
 * - int: absolute start index; negative values are end-relative (e.g. -1 means the match ends 1 element before end); 0 == 'start'
 * Negative $at on non-countable iterables throws an InvalidConfigException.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Contains extends ValidatorBase
{
    use SequenceMatcher;

    /**
     * @param 'start'|'end'|int|null $at
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(string | iterable $needle, string | int | null $at = null)
    {
        if (\is_int($at) && $at < 0 && \is_iterable($needle) && !\is_countable($needle)) {
            throw new \Nandan108\DtoToolkit\Exception\Config\InvalidConfigException(
                "Contains validator: negative '\$at' requires a countable iterable.",
            );
        }

        parent::__construct([$needle, $at]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$needle, $at] = $args;
        $this->assertValidPosition($at);

        if (\is_string($value)) {
            if (!\is_string($needle)) {
                $this->throwTypeMismatch($value);
            }

            if (!$this->containsString($value, $needle, $at)) {
                $this->throwNotContained($value);
            }

            return;
        }

        if (\is_iterable($value)) {
            if (!\is_iterable($needle)) {
                $this->throwTypeMismatch($value);
            }

            if (!$this->isRewindableIterable($value) || !$this->isRewindableIterable($needle)) {
                $this->throwNonRewindable($value);
            }

            $haystack = $this->iterableToList($value);
            $needleSeq = $this->iterableToList($needle);

            if (!$this->containsSequence($haystack, $needleSeq, $at)) {
                $this->throwNotContained($value);
            }

            return;
        }

        $this->throwTypeMismatch($value);
    }

    private function throwTypeMismatch(mixed $value): never
    {
        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'contains.type_mismatch',
            methodOrClass: self::class,
        );
    }

    private function throwNonRewindable(mixed $value): never
    {
        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'contains.non_rewindable',
            methodOrClass: self::class,
        );
    }

    private function throwNotContained(mixed $value): never
    {
        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'contains.not_contained',
            methodOrClass: self::class,
        );
    }
}
