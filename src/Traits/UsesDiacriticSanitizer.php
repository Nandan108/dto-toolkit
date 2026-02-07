<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * To be used by casters that need to remove diacritics (i.e. accents and other marks) from a string's characters.
 */
trait UsesDiacriticSanitizer
{
    public static bool $useIntlExtension = true;
    protected static string $transliterateParams = 'Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove';

    /** @var \Transliterator[] */
    protected static array $_transliterators = [];

    protected static function removeDiacritics(string $value, ?bool $useIntlExtension = null): string
    {
        $useIntlExtension ??= static::$useIntlExtension;

        if ($useIntlExtension && extension_loaded('intl')) {
            return static::removeDiacriticsWithIntl($value);
        }

        return static::sanitizeWithStrtr($value);
    }

    public static function removeDiacriticsWithIntl(string $value): string
    {
        $transliterator = static::getIntlTransliterator();

        $result = $transliterator->transliterate($value);

        // Transliteration failure is very unlikely to happen, but if it does, we throw an exception
        false !== $result || throw TransformException::reason(
            value: $value,
            // Failed to transliterate string with Intl extension
            template_suffix: 'intl.transliterate_failed',
            parameters: ['transliterateParams' => static::$transliterateParams],
        );

        return $result;
    }

    protected static function getIntlTransliterator(): \Transliterator
    {
        $params = static::$transliterateParams;

        if (isset(static::$_transliterators[static::$transliterateParams])) {
            return static::$_transliterators[static::$transliterateParams];
        }

        $transliterator = \Transliterator::create($params);
        if (null === $transliterator) {
            /** @var string $params */
            $params = json_encode($params);
            throw new InvalidArgumentException("Invalid transliteration parameters: $params");
        }

        return static::$_transliterators[$params] = $transliterator;
    }

    public static function sanitizeWithStrtr(string $value): string
    {
        // Rudimentary fallback for common accents (incomplete but helpful)
        static $map = [
            'à'=> 'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'è'=> 'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
            'ì'=> 'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'ò'=> 'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ù'=> 'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u',
            'ç'=> 'c', 'ñ'=>'n',
            'À'=> 'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
            'È'=> 'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'Ì'=> 'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ò'=> 'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',
            'Ù'=> 'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U',
            'Ç'=> 'C', 'Ñ'=>'N',
        ];

        return strtr($value, $map);
    }
}
