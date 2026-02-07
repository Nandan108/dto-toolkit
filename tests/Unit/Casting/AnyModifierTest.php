<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\ChainModifier\FailIf;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class AnyModifierTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testAnyModifier(): void
    {
        // Test the Collect modifier
        $dto = new class extends FullDto {
            // Using an int argument
            #[CastTo\FromJson]
            #[Mod\Any(4)]
            // fails to return array unless context.allowArray is true
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar'), FailIf('<context:allowArray', negate: true)]
            // fails to cast "not-a-number" to float
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar.baz'), CastTo\Floating('.')]
            // succeeds to cast "123.4" to float
            /* - */ #[Mod\Wrap(2)]
            /* --- */ #[Mod\Groups('qux', 2), CastTo\Extract('foo.bar.qux'), CastTo\Floating('.')]
            /* --- */ #[CastTo\Str]
            // skipped because of the previous success
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar.fux'), CastTo\Floating('.')]
            public mixed $value = null;
        };

        $fooBarInput = '{"baz": "not-a-number", "qux": "123.4", "fux": [123]}';
        $input = "{\"foo\": {\"bar\": $fooBarInput}}";

        // Test that FailIf modifier doesn't fail fails when when condition is true

        $dto->withContext(['allowArray' => true])->loadArray(['value' => $input]);
        $this->assertSame(json_decode($fooBarInput, true), $dto->value);
        // Test that FailIf modifier fails when condition is false, Floating fails to cast "not-a-number" to float

        $dto->withContext(['allowArray' => false])->withGroups('qux')->loadArray(['value' => $input]);
        $this->assertSame('123.4', $dto->value);

        try {
            // Test that Any fails when all nodes fail

            $dto->withGroups('fux')->loadArray(['value' => $input]);
        } catch (ProcessingException $e) {
            $this->assertStringContainsString('processing.modifier.first_success.all_failed', $e->getMessage());
            $this->assertSame(4, $e->getMessageParameters()['strategy_count']);
        }
    }

    public function testAnyWithValidators(): void
    {
        $dto = new class extends FullDto {
            #[Mod\Any(2)]
            #[Assert\Range(min: 1, max: 3)]
            #[Assert\Range(min: 10, max: 12)]
            public mixed $num = null;
        };

        $dto->fill(['num' => 11])->processInbound();
        $dto->fill(['num' => 2])->processInbound();

        try {
            $dto->fill(['num' => 100])->processInbound();
            $this->fail('Expected ProcessingException not thrown');
        } catch (ProcessingException $e) {
            $this->assertStringContainsString('processing.modifier.first_success.all_failed', $e->getMessage());
            $this->assertSame(2, $e->getMessageParameters()['strategy_count']);
            $this->assertSame('num{Mod\\Any}', $e->getPropertyPath());
        }
    }

    public function testFailIfThrowsProcessingException(): void
    {
        $dto = new class extends FullDto {
            #[FailIf(AlwaysTrueCondition::class.'::provide')]
            public mixed $value = null;
        };

        $caught = false;
        try {
            $dto->fill(['value' => 'anything'])->processInbound();
        } catch (ProcessingException $e) {
            $caught = true;
            $this->assertStringContainsString('processing.modifier.fail_if.condition_failed', $e->getMessage());
        }
        $this->assertTrue($caught);
    }
}

final class AlwaysTrueCondition
{
    /** @psalm-suppress UnusedParam, PossiblyUnusedMethod */
    public static function provide(mixed $value, ?string $prop, BaseDto $dto): bool
    {
        return true;
    }
}
