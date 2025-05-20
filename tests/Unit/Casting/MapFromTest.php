<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class MapFromTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testMapFromExistingInput(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['baz', 'bar']])]
            public string|array|null $bar = '';

            #[MapFrom('bar')]
            public string|array|null $boo = null;
        };

        $dto = $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            'baz' => 'BAZ-val',
        ]);
        $this->assertSame(['qux1' => 'FOO-val', 'qux2' => ['BAZ-val', 'BAR-val']], $dto->bar);
        $this->assertSame('BAR-val', $dto->boo);

        $dto = $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
        ]);
        $this->assertSame(['qux1' => 'FOO-val', 'qux2' => [null, 'BAR-val']], $dto->bar);
    }

    public function testMapFromMissingInputThrowsIfLooseRequiredByOneBang(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['!baz', 'bar']])]
            public string|array|null $bar = '';
        };

        $dto = $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            // null value doesn't throw
            'baz' => null,
        ]);
        $this->assertSame(['qux1' => 'FOO-val', 'qux2' => [null, 'BAR-val']], $dto->bar);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key \'baz\' not found in input values');
        /** @psalm-suppress UnusedVariable */
        $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
        ]);
    }

    public function testMapFromNullInputThrowsIfStrictRequiredByTwoBangs(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['!!baz', 'bar']])]
            public string|array|null $bar = '';
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Key \'baz\' should not be blank');
        $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            'baz' => null, // null value will throw in this case (!!baz)
        ]);
    }

    public function testMapFromThrowsIfUsedInOutboundPhase(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The MapFrom attribute cannot be used in the outbound phase.');

        $dtoClass = new class extends FullDto {
            #[Outbound]
            #[MapFrom('foo')]
            public string|array|null $bar = '';
        };
        $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
        ]);
    }
}
