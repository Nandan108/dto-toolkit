<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

// use Nandan108\DtoToolkit\Core\CastTo;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException as ConfigInvalidArgumentException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use Nandan108\DtoToolkit\Traits\UsesDiacriticSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DiacriticsSanitizerTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testCasterRemovesDiacritics(): void
    {
        // parent-constructor call line in caster constructor is not covered
        // if constructor is instanciated within the DataProvider method, for some reason. So test them separately.
        $this->casterTest(new CastTo\Slug('.'), 'Let\'s go for Smörgåsbord', 'let.s.go.for.smorgasbord');
    }

    public function testDiacriticsSanitizerThrowsOnInvalidTransliterationParams(): void
    {
        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid transliteration parameters');

        $testClass = new class {
            use UsesDiacriticSanitizer;

            public function __construct()
            {
                $this::$transliterateParams = 'invalid-params';
            }

            public function cleanString(string $value): string
            {
                return $this->removeDiacritics($value);
            }
        };

        $testClass->cleanString('foo');
    }
}
