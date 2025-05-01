<?php

namespace Nandan108\DtoToolkit\Support;

final class CaseConverter
{
    /**
     * Breaks an identifier into normalized lowercase words,
     * then applies optional transformations to each word.
     * Returns the words joined by $separator.
     *
     * @param string     $str        The input string
     * @param callable[] $transforms Callable(s) to apply to each word (e.g. 'ucfirst', ['strtolower', 'ucfirst'])
     * @param string     $separator  The separator to use when joining the words
     *
     * @return string The final transformed string
     */
    public static function normalizeWords(string $str, array|callable $transforms, $separator): string
    {
        // Step 1a: remove non-alphabetic characters and replace them with underscores
        $str = preg_replace('/[^a-z]+/i', '_', $str) ?? '';
        // Step 1b: split the string into words
        /** @var string[] $words */
        $words = preg_split('/_|(?<=[a-z])(?=[A-Z])/', $str);

        // Step 2.b: apply transformations
        foreach ($transforms as $fn) {
            $words = array_map($fn, $words);
        }

        // Step 3: return words joined with the specified separator
        return implode($separator, $words);
    }

    public static function toCamel(string $str): string
    {
        return lcfirst(self::normalizeWords($str, ['\strtolower', '\ucfirst'], ''));
    }

    public static function toPascal(string $str): string
    {
        return self::normalizeWords($str, ['\strtolower', '\ucfirst'], '');
    }

    public static function toSnake(string $str): string
    {
        return self::normalizeWords($str, ['strtolower'], '_');
    }

    public static function toKebab(string $str): string
    {
        return self::normalizeWords($str, ['strtolower'], '-');
    }

    public static function toUpperSnake(string $str): string
    {
        return self::normalizeWords($str, ['strtoupper'], '_');
    }
}
