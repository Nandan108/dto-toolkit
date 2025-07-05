<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\LoadingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class MappingTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testMapFromExistingInput(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['baz', 'bar']])]
            public string|array|null $bar = '';

            #[MapFrom('bar')]
            public string|array|null $boo = null;

            // no MapFrom attribute means it is copied as-is
            public ?string $fiz = null;
        };

        $dto = $dtoClass::fromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            'baz' => 'BAZ-val',
            'fiz' => 'FIZ-val',
        ]);
        $this->assertSame([
            'qux1' => 'FOO-val',
            'qux2' => ['BAZ-val', 'BAR-val'],
        ],
            $dto->bar);
        $this->assertSame('BAR-val', $dto->boo);
        $this->assertSame('FIZ-val', $dto->fiz);

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

        $this->expectException(LoadingException::class);
        $this->expectExceptionMessage('Path segment $input.`baz` not found in array.');
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

        $this->expectException(LoadingException::class);
        $this->expectExceptionMessage('Path segment $input.`baz` is null but required.');
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

    public function testMapTo(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapTo('foo')]
            public string|array|null $bar = '';

            #[MapTo(null)]
            public ?string $notExported = null;
        };
        $dto = $dtoClass::fromArray(['bar' => 'BAR-val', 'notExported' => 'NOT-exported-val']);
        $out = $dto->toOutboundArray();

        $this->assertSame(['foo' => 'BAR-val'], $out);
    }

    public function testMapFromFailsWithInvalidPath(): void
    {
        // Extract with invalid path input
        try {
            new MapFrom('foo.-bar!');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
            $this->assertStringContainsString('Invalid path provided', $e->getMessage());
        }
    }
}
