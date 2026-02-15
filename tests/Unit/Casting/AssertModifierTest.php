<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use PHPUnit\Framework\TestCase;

final class AssertModifierTest extends TestCase
{
    public function testAllRunsAllNodesWithSameInput(): void
    {
        $dto = new class extends FullDto {
            public array $calls = [];

            #[Mod\Assert(2)]
            #[CastTo('first')]
            #[CastTo('second')]
            public mixed $value = null;

            /** @psalm-suppress PossiblyUnusedMethod */
            public function castToFirst(string $value): string
            {
                $this->calls[] = "first:$value";

                return $value.'-first';
            }

            /** @psalm-suppress PossiblyUnusedMethod */
            public function castToSecond(string $value): string
            {
                $this->calls[] = "second:$value";

                return $value.'-second';
            }
        };

        $dto->fill(['value' => 'orig']);
        $dto->processInbound();

        $this->assertSame(['first:orig', 'second:orig'], $dto->calls);
        $this->assertSame('orig', $dto->value);
    }

    public function testAllBubblesFailuresAfterRunningPriorNodes(): void
    {
        $dto = new class extends FullDto {
            public array $calls = [];

            #[Mod\Assert(2),
                CastTo('first'),
                CastTo\Boolean]
            public mixed $value = null;

            /** @psalm-suppress PossiblyUnusedMethod */
            public function castToFirst(string $value): string
            {
                $this->calls[] = "first:$value";

                return $value;
            }
        };

        $dto->fill(['value' => 'not-a-bool']);

        try {
            $dto->processInbound();
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('processing.transform.boolean.unable_to_cast', $e->getMessageTemplate());

            $this->assertSame('value{Mod\Assert}[1]{CastTo\Boolean}', $e->getPropertyPath());
            $this->assertSame(['first:not-a-bool'], $dto->calls);
        }
    }
}
