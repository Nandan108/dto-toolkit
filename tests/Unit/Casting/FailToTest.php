<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert as V;
use Nandan108\DtoToolkit\Attribute\ChainModifier\FailTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException as ConfigInvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
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
        $dto->processInbound();
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
        ])->processInbound();

        $this->assertSame('RECOVERED TO FALLBACK', $dto->value_1);
        $this->assertSame('UNCOVERED TO BACKFALL', $dto->value_2);
        $this->assertTrue($dto->context['called']);
        $this->assertSame('processing.transform.stringable.expected', $dto->context['message']);
    }

    public function testBadFailureHandlerThrows(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\Str]
            #[FailTo(fallback: 'failback', handler: ['BadClass', 'wrongHandler'])]
            #[CastTo\Uppercase]
            public mixed $value_fail = null;
        };

        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FailTo handler: ["BadClass","wrongHandler"], expected DTO method name or valid [class, staticMethod] callable.');

        // use a \stdClass since casting it to a string will throw
        $dto->processInbound();
    }

    public function testBadUsageAsFirstInChain(): void
    {
        $dto = new class extends FullDto {
            #[FailTo(fallback: 'failback')]
            #[CastTo\Str]
            public mixed $value_fail = null;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('should not be used as the first element');

        // use a \stdClass since casting it to a string will throw
        $dto->fill(['value_fail' => new \stdClass()]);
        $dto->processInbound();
    }

    public function testFallbackHandlesValidationFailure(): void
    {
        $dto = new class extends FullDto {
            #[V\IsBlank(false)]
            #[FailTo(fallback: 'fallback')]
            public mixed $name = null;
        };

        $dto->fill(['name' => '   '])->processInbound();
        $this->assertSame('fallback', $dto->name);
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
