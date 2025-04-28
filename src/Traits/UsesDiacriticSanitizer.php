<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * To be used by casters that need to remove diacritics (i.e. accents and other marks) from a string's characters.
 */
trait UsesDiacriticSanitizer
{
    public static bool $useIntlExtension = true;
    protected static string $transliterateParams = 'Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove';
    protected static array $_transliterators = [];

    protected static function removeDiacritics(string $value, ?bool $useIntlExtension = null): string
    {
        $useIntlExtension ??= static::$useIntlExtension;

        if ($useIntlExtension && extension_loaded('intl')) {
            $params = static::$transliterateParams;
            $transliterator = static::$_transliterators[$params] ??= \Transliterator::create($params);

            // Could fail in a subclass that tries to use invalid $transliterateParams
            if (null === $transliterator) {
                throw CastingException::castingFailure(static::class, $value, "Transliterator::create('$params') failed");
            }

            set_error_handler(function ($errno, $errstr) use ($value) {
                throw CastingException::castingFailure(static::class, $value, 'Transliterator failed to remove diacritics: '.$errstr);
            });

            try {
                return $transliterator->transliterate($value);
            } finally {
                restore_error_handler();
            }
        }

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
