<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class CoalesceCastTest extends TestCase
{
    public function testCoalesceReturnsFirstNonIgnored(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: [null, ''], fallback: 'N/A')]
            public mixed $value = null;
        };

        $dto->fill(['value' => [null, '', 'ok']])->processInbound();

        $this->assertSame('ok', $dto->value);
    }

    public function testCoalesceFallsBackWhenAllIgnored(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: [null, ''], fallback: 'N/A')]
            public mixed $value = null;
        };

        $dto->fill(['value' => [null, '']])->processInbound();

        $this->assertSame('N/A', $dto->value);
    }

    public function testCoalesceThrowsWhenNoFallback(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: [null, ''])]
            public mixed $value = null;
        };

        $this->expectException(TransformException::class);
        $dto->fill(['value' => [null, '']])->processInbound();
    }

    public function testCoalesceAcceptsTraversableIgnoreList(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: new \ArrayIterator([null, '']))]
            public mixed $value = null;
        };

        $dto->fill(['value' => [null, 'ok']])->processInbound();

        $this->assertSame('ok', $dto->value);
    }

    public function testCoalesceAcceptsTraversableValue(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: [null])]
            public mixed $value = null;
        };

        $dto->fill(['value' => new \ArrayIterator([null, 'ok'])])->processInbound();

        $this->assertSame('ok', $dto->value);
    }

    public function testCoalesceRejectsNonIterableValue(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Coalesce(ignore: [null])]
            public mixed $value = null;
        };

        $this->expectException(TransformException::class);
        $dto->fill(['value' => 'nope'])->processInbound();
    }
}
