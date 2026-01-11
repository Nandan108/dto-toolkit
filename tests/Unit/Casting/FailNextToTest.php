<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert as V;
use Nandan108\DtoToolkit\Attribute\ChainModifier\FailNextTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException as ConfigInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FailNextToTest extends TestCase
{
    public function testFallbackValueIsUsedOnFailure(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\ReplaceWhen('foo', ['bar'], )]
            #[FailNextTo('fallback')]
            #[CastTo\Str]
            public mixed $value = null;
        };

        // 'foo' gets replaced by 'bar' and then fails to be cast to string, falling back to 'fallback'
        $dto->fill(['value' => 'foo'])->processInbound();
        $this->assertSame('fallback', $dto->value);

        // 'bar' doesn't get replaced and is cast to string successfully
        $dto->fill(['value' => 'bar'])->processInbound();
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('bar', $dto->value);
    }

    public function testThrowsIfFailNextToCountIsLessThanOne(): void
    {
        $dto = new class extends FullDto {
            #[FailNextTo('fallback', count: 0)]
            #[CastTo\Str]
            public mixed $value = null;
        };

        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('FailNextTo: $count cannot be zero.');

        $dto->fill(['value' => new \stdClass()]);
        $dto->processInbound();
    }

    public function testMethodHandlerOnDtoIsCalled(): void
    {
        $dto = new class extends FullDto {
            public array $context = [];
            #[FailNextTo(fallback: 'fallback', handler: 'handleFail', count: 1)]
            #[CastTo\Str]
            #[CastTo\Uppercase]
            public mixed $value_1 = null;

            #[FailNextTo(fallback: 'backfall', handler: [FailNextTo_CastFailureHandler::class, 'handleFail'], count: 1)]
            #[CastTo\Str]
            #[CastTo\Uppercase]
            public mixed $value_2 = null;

            public function handleFail(mixed $value, mixed $fallback, \Throwable $e, BaseDto $dto): string
            {
                $this->context['called'] = true;
                $this->context['message'] = $e->getMessage();

                return 'recovered to '.$fallback;
            }
        };

        // use a \stdClass since casting it to a string will throw
        $dto->fill([
            'value_1' => new \stdClass(),
            'value_2' => new \stdClass(),
        ]);
        $dto->processInbound();

        $this->assertSame('RECOVERED TO FALLBACK', $dto->value_1);
        $this->assertSame('UNCOVERED TO BACKFALL', $dto->value_2);
        $this->assertTrue($dto->context['called']);
        $this->assertSame('processing.transform.stringable.expected', $dto->context['message']);
    }

    public function testFallbackCatchesValidationFailure(): void
    {
        $dto = new class extends FullDto {
            #[FailNextTo('fallback', count: 1)]
            #[V\IsBlank(false)]
            public mixed $name = null;
        };

        $dto->fill(['name' => '   '])->processInbound();
        $this->assertSame('fallback', $dto->name);
    }
}

final class FailNextTo_CastFailureHandler
{
    /** @psalm-suppress PossiblyUnusedParam, PossiblyUnusedMethod, UnusedParam*/
    public static function handleFail(mixed $value, mixed $fallback, \Throwable $e, BaseDto $dto): string
    {
        return 'uncovered to '.$fallback;
    }
}
