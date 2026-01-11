<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert\Support;

trait SequenceMatcher
{
    private function assertValidPosition(string | int | null $at): void
    {
        if (null !== $at && !\is_int($at) && 'start' !== $at && 'end' !== $at) {
            throw new \Nandan108\DtoToolkit\Exception\Config\InvalidConfigException(
                "Contains/ContainedIn validator: invalid 'at' position '{$at}'.",
            );
        }
    }

    private function isRewindableIterable(mixed $value): bool
    {
        if (\is_array($value)) {
            return true;
        }

        if ($value instanceof \Generator) {
            return false;
        }

        if ($value instanceof \IteratorAggregate) {
            return true;
        }

        if ($value instanceof \Iterator) {
            return true;
        }

        return false;
    }

    private function iterableToList(iterable $value): array
    {
        if (\is_array($value)) {
            return array_values($value);
        }

        return array_values(iterator_to_array($value, false));
    }

    private function containsString(string $haystack, string $needle, string | int | null $at): bool
    {
        if ('' === $needle) {
            return true;
        }

        if (\is_int($at)) {
            $len = strlen($haystack);
            $needleLen = strlen($needle);

            if ($at < 0) {
                $endIndex = $len + $at;
                if ($endIndex < 0 || $endIndex > $len) {
                    return false;
                }

                $start = $endIndex - $needleLen;
                if ($start < 0) {
                    return false;
                }

                return substr($haystack, $start, $needleLen) === $needle;
            }

            if ($at > $len) {
                return false;
            }

            return substr($haystack, $at, $needleLen) === $needle;
        }

        return match ($at) {
            'start' => str_starts_with($haystack, $needle),
            'end'   => str_ends_with($haystack, $needle),
            default => str_contains($haystack, $needle),
        };
    }

    private function containsSequence(array $haystack, array $needle, string | int | null $at): bool
    {
        if ([] === $needle) {
            return true;
        }

        $needleCount = count($needle);
        $haystackCount = count($haystack);

        if ($needleCount > $haystackCount) {
            return false;
        }

        if (\is_int($at)) {
            if ($at < 0) {
                $endIndex = $haystackCount + $at;
                if ($endIndex < 0 || $endIndex > $haystackCount) {
                    return false;
                }

                $start = $endIndex - $needleCount;
                if ($start < 0) {
                    return false;
                }

                return array_slice($haystack, $start, $needleCount) === $needle;
            }

            if ($at > $haystackCount) {
                return false;
            }

            return array_slice($haystack, $at, $needleCount) === $needle;
        }

        return match ($at) {
            'start' => array_slice($haystack, 0, $needleCount) === $needle,
            'end'   => array_slice($haystack, -$needleCount) === $needle,
            default => $this->containsSequenceAnywhere($haystack, $needle),
        };
    }

    private function containsSequenceAnywhere(array $haystack, array $needle): bool
    {
        $needleCount = count($needle);
        $limit = count($haystack) - $needleCount;

        for ($i = 0; $i <= $limit; ++$i) {
            if (array_slice($haystack, $i, $needleCount) === $needle) {
                return true;
            }
        }

        return false;
    }
}
