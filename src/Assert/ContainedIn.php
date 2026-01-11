<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Assert\Support\SequenceMatcher;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a string or sequence value is contained within the given haystack. Types must match (string or iterable).
 * The optional $at position can be:
 * - null: anywhere
 * - 'start' or 'end': anchored match
 * - int: absolute start index; negative values are end-relative (e.g. -1 means the match ends 1 element before end); 0 == 'start'.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class ContainedIn extends ValidatorBase
{
    use SequenceMatcher;

    /**
     * @param 'start'|'end'|int|null $at
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(string | iterable $haystack, string | int | null $at = null)
    {
        if (\is_int($at) && $at < 0 && \is_iterable($haystack) && !\is_countable($haystack)) {
            throw new \Nandan108\DtoToolkit\Exception\Config\InvalidConfigException(
                "ContainedIn validator: negative '\$at' requires a countable iterable.",
            );
        }

        parent::__construct([$haystack, $at]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$haystack, $at] = $args;
        $this->assertValidPosition($at);

        if (\is_string($value)) {
            if (!\is_string($haystack)) {
                $this->throwTypeMismatch($value);
            }

            if (!$this->containsString($haystack, $value, $at)) {
                $this->throwNotContained($value);
            }

            return;
        }

        if (\is_iterable($value)) {
            if (!\is_iterable($haystack)) {
                $this->throwTypeMismatch($value);
            }

            if (!$this->isRewindableIterable($value) || !$this->isRewindableIterable($haystack)) {
                $this->throwNonRewindable($value);
            }

            $needle = $this->iterableToList($value);
            $haystackSeq = $this->iterableToList($haystack);

            if (!$this->containsSequence($haystackSeq, $needle, $at)) {
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
            template_suffix: 'contained_in.type_mismatch',
            methodOrClass: self::class,
        );
    }

    private function throwNonRewindable(mixed $value): never
    {
        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'contained_in.non_rewindable',
            methodOrClass: self::class,
        );
    }

    private function throwNotContained(mixed $value): never
    {
        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'contained_in.not_contained',
            methodOrClass: self::class,
        );
    }
}
