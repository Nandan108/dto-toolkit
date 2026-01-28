<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class PadCastTest extends TestCase
{
    public function testPadRight(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Pad(5, '_')]
            public string $name = '';
        };

        $dto->fill(['name' => 'ab'])->processInbound();

        $this->assertSame('ab___', $dto->name);
    }

    public function testPadLeft(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\Pad(4, '0', STR_PAD_LEFT)]
            public string $code = '';
        };

        $dto->fill(['code' => '7'])->processInbound();

        $this->assertSame('0007', $dto->code);
    }

    public function testPadRejectsInvalidLength(): void
    {
        $this->expectException(InvalidConfigException::class);

        new CastTo\Pad(0);
    }

    public function testPadRejectsInvalidPadType(): void
    {
        $this->expectException(InvalidConfigException::class);

        new CastTo\Pad(3, '_', 999);
    }
}
