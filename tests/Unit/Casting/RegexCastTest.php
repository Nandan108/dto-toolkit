<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class RegexCastTest extends TestCase
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
            [$goodRegex, $replaceStr],
        );

        $this->casterTest(
            new CastTo\RegexReplace($badRegex, $replaceStr),
            $orgStr,
            TransformException::class,
            [$badRegex, $replaceStr],
            'processing.transform.regex.replace_failed',
        );
    }

    public function testRegexSplit(): void
    {
        $goodRegex = '/\b/';
        $badRegex = '/*]/';
        $input = 'hello.world';

        $this->casterTest(
            new CastTo\RegexSplit($goodRegex),
            $input,
            ['', 'hello', '.', 'world', ''],
            [$goodRegex],
        );

        $this->casterTest(
            new CastTo\RegexSplit($badRegex),
            $input,
            TransformException::class,
            [$badRegex],
            'processing.transform.regex.split_failed',
        );
    }
}
