<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert\Regex as RegexAssert;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
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

        $this->expectException(InvalidArgumentException::class);
        new CastTo\RegexReplace($badRegex, $replaceStr);
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

        $this->expectException(InvalidArgumentException::class);
        new CastTo\RegexSplit($badRegex);
    }

    public function testRegexReplaceThrowsTransformExceptionOnRuntimeMatchingFailure(): void
    {
        $caster = new CastTo\RegexReplace('/./u', 'x');
        $invalidUtf8 = "\xC3\x28";
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());

        ProcessingContext::pushFrame($frame);
        try {
            $caster->cast($invalidUtf8, ['/./u', 'x', -1]);
            $this->fail('Expected TransformException was not thrown.');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.regex.replace_failed', $e->getMessageTemplate());
            $this->assertSame('transform.regex', $e->getErrorCode());
        } finally {
            ProcessingContext::popFrame();
        }
    }

    public function testRegexSplitThrowsTransformExceptionOnRuntimeMatchingFailure(): void
    {
        $caster = new CastTo\RegexSplit('/./u');
        $invalidUtf8 = "\xC3\x28";
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());

        ProcessingContext::pushFrame($frame);
        try {
            $caster->cast($invalidUtf8, ['/./u', -1]);
            $this->fail('Expected TransformException was not thrown.');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.regex.split_failed', $e->getMessageTemplate());
            $this->assertSame('transform.regex', $e->getErrorCode());
        } finally {
            ProcessingContext::popFrame();
        }
    }

    public function testRegexValidatorThrowsGuardExceptionOnRuntimeMatchingFailure(): void
    {
        $validator = new RegexAssert('/./u');
        $invalidUtf8 = "\xC3\x28";
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());

        ProcessingContext::pushFrame($frame);
        try {
            $validator->validate($invalidUtf8, ['/./u', false]);
            $this->fail('Expected GuardException was not thrown.');
        } catch (GuardException $e) {
            $this->assertSame('processing.guard.regex.matching_failed', $e->getMessageTemplate());
            $this->assertSame('guard.regex', $e->getErrorCode());
        } finally {
            ProcessingContext::popFrame();
        }
    }
}
