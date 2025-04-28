<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\CastModifier\FailTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use PHPUnit\Framework\TestCase;

final class FailToTest extends TestCase
{
    public function testFallbackValueIsUsedOnFailure(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\Str]
            #[FailTo('fallback')]
            public mixed $value = null;
        };

        $dto->fill(['value' => new \stdClass()]);
        $dto->normalizeInbound();
        $this->assertSame('fallback', $dto->value);
    }

    public function testMethodHandlerOnDtoIsCalled(): void
    {
        $dto = new class extends FullDto {
            public array $context = [];
            #[CastTo\Str]
            #[FailTo(fallback: 'fallback', handler: 'handleFail')]
            #[CastTo\Uppercase]
            public mixed $value_1 = null;

            #[CastTo\Str]
            #[FailTo(fallback: 'backfall', handler: [FailTo_CastFailureHandler::class, 'handleFail'])]
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
        /** @psalm-suppress UnusedMethodCall */
        $dto->fill([
            'value_1' => new \stdClass(),
            'value_2' => new \stdClass(),
        ])->normalizeInbound();

        $this->assertSame('RECOVERED TO FALLBACK', $dto->value_1);
        $this->assertSame('UNCOVERED TO BACKFALL', $dto->value_2);
        $this->assertTrue($dto->context['called']);
        $this->assertMatchesRegularExpression(
            '/Expected: numeric, string or Stringable/',
            $dto->context['message'],
        );
    }

    public function testBadFailureHandlerThrows(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\Str]
            #[FailTo(fallback: 'failback', handler: ['BadClass', 'wrongHandler'])]
            #[CastTo\Uppercase]
            public mixed $value_fail = null;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FailTo handler: ["BadClass","wrongHandler"], expected DTO method name or valid [class, staticMethod] callable.');

        // use a \stdClass since casting it to a string will throw
        $dto->normalizeInbound();
    }
}

final class FailTo_CastFailureHandler
{
    /** @psalm-suppress PossiblyUnusedMethod, UnusedParam, PossiblyUnusedParam */
    public static function handleFail(mixed $value, mixed $fallback): string
    {
        return 'uncovered to '.$fallback;
    }
}
