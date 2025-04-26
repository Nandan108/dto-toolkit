<?php

namespace Nandan108\DtoToolkit\Traits;

trait UsesDiacriticSanitizer
{
    protected static function removeDiacritics(string $value): string
    {
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove');
            if ($transliterator) {
                return $transliterator->transliterate($value) ?? $value;
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
