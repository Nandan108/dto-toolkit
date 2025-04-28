<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class RegexReplaceCastTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testRegexReplace(): void
    {
        $goodRegex = '/hi/';
        $badRegex = '/*]/';
        $replaceStr = 'lo';
        $orgStr = 'helhi world';

        $this->casterTest(
            new CastTo\RegexReplace($goodRegex, $replaceStr),
            $orgStr,
            'hello world',
            [$goodRegex, $replaceStr]
        );

        $this->casterTest(
            new CastTo\RegexReplace($badRegex, $replaceStr),
            $orgStr,
            CastingException::class,
            [$badRegex, $replaceStr],
            'Compilation failed'
        );
    }
}
