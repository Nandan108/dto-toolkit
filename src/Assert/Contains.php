<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Assert\Support\SequenceMatcher;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a string or sequence value contains the given needle of same type (string or iterable).
 * The optional $at position can be:
 * - null: anywhere
 * - 'start' or 'end': anchored match
 * - int: absolute start index; negative values are end-relative (e.g. -1 means the match ends 1 element before end); 0 == 'start'
 * Negative $at on non-countable iterables throws an InvalidArgumentException.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Contains extends ValidatorBase
{
    use SequenceMatcher;

    /**
     * @param 'start'|'end'|int|null $at
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function __construct(string | iterable $needle, string | int | null $at = null, bool $caseSensitive = true)
    {
        if (\is_int($at) && $at < 0 && \is_iterable($needle) && !\is_countable($needle)) {
            throw new InvalidArgumentException(
                "Contains validator: negative '\$at' requires a countable iterable.",
            );
        }

        parent::__construct([$needle, $at, $caseSensitive]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: string|iterable, 1: 'start'|'end'|int|null, 2: bool} $args */
        [$needle, $at, $caseSensitive] = $args;
        $this->assertValidPosition($at, 'Contains');

        if (\is_string($value)) {
            if (!\is_string($needle)) {
                $this->throwTypeMismatch($value);
            }

            if (!$caseSensitive) {
                $value = $this->toLower($value);
                $needle = $this->toLower($needle);
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

            if (!$caseSensitive) {
                throw new InvalidArgumentException('Contains validator: caseSensitive=false requires a string needle.');
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

    /** @return lowercase-string */
    private function toLower(string $value): string
    {
        // TODO: Remove this trick when bumping PHP requirement to 8.3+
        /** @var callable(string):lowercase-string */
        $stringToLower = \function_exists('mb_strtolower') ? \mb_strtolower(...) : \strtolower(...);

        return $stringToLower($value);
    }

    private function throwTypeMismatch(mixed $value): never
    {
        throw GuardException::reason(
            value: $value,
            template_suffix: 'contains.type_mismatch',
            errorCode: 'guard.contains',
        );
    }

    private function throwNonRewindable(mixed $value): never
    {
        throw GuardException::reason(
            value: $value,
            template_suffix: 'contains.non_rewindable',
            errorCode: 'guard.contains',
        );
    }

    private function throwNotContained(mixed $value): never
    {
        throw GuardException::reason(
            value: $value,
            template_suffix: 'contains.not_contained',
            errorCode: 'guard.contains',
        );
    }
}
