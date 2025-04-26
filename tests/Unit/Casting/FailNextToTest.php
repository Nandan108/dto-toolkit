<?php

namespace Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\CastModifier\FailNextTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use PHPUnit\Framework\TestCase;

final class FailNextToTest extends TestCase
{
    public function testFallbackValueIsUsedOnFailure(): void
    {
        $dto = new class extends FullDto {
            #[FailNextTo('fallback')]
            #[CastTo\Str]
            public mixed $value = null;
        };

        $dto->fill(['value' => new \stdClass()]);
        $dto->normalizeInbound();
        $this->assertSame('fallback', $dto->value);
    }

    public function testThrowsIfFailNextToCountIsLessThanOne(): void
    {
        $dto = new class extends FullDto {
            #[FailNextTo('fallback', count: 0)]
            #[CastTo\Str]
            public mixed $value = null;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FailNextTo: $count must be greater than or equal to 1.');

        $dto->fill(['value' => new \stdClass()]);
        $dto->normalizeInbound();
    }

    public function testMethodHandlerOnDtoIsCalled(): void
    {
        $dto = new class extends FullDto {
            public array $context = [];
            #[FailNextTo(fallback: 'fallback', handler: 'handleFail')]
            #[CastTo\Str]
            #[CastTo\Uppercase]
            public mixed $value_1 = null;

            #[FailNextTo(fallback: 'backfall', handler: [FailNextTo_CastFailureHandler::class, 'handleFail'])]
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
        $dto->normalizeInbound();

        $this->assertSame('RECOVERED TO FALLBACK', $dto->value_1);
        $this->assertSame('UNCOVERED TO BACKFALL', $dto->value_2);
        $this->assertTrue($dto->context['called']);
        $this->assertMatchesRegularExpression(
            '/Expected: numeric, string or Stringable/',
            $dto->context['message'],
        );
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
