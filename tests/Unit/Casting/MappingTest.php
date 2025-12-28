<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use Nandan108\PropPath\Support\ThrowMode;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\never;

final class MappingTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testMapFromExistingInput(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['baz', 'bar']], ThrowMode::NEVER)]
            public string | array | null $bar = '';

            #[MapFrom('bar')]
            public string | array | null $boo = null;

            // no MapFrom attribute means it is copied as-is
            public ?string $fiz = null;
        };

        $dto = $dtoClass::newFromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            'baz' => 'BAZ-val',
            'fiz' => 'FIZ-val',
        ]);
        $this->assertSame(
            [
                'qux1' => 'FOO-val',
                'qux2' => ['BAZ-val', 'BAR-val'],
            ],
            $dto->bar,
        );
        $this->assertSame('BAR-val', $dto->boo);
        $this->assertSame('FIZ-val', $dto->fiz);

        $dto = $dtoClass::newFromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            // 'baz' is missing, so bar prop's mapper fails.
            // But since we used ThrowMode::NEVER, it just returns a null value.
        ]);
        $this->assertSame(['qux1' => 'FOO-val', 'qux2' => [null, 'BAR-val']], $dto->bar);
    }

    public function testMapFromMissingInputThrowsIfLooseRequiredByOneBang(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['!baz', 'bar']], ThrowMode::NEVER)]
            public string | array | null $bar = '';
        };

        $dto = $dtoClass::newFromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            // default throw mode = ThrowMode::NEVER -> null value doesn't throw
            'baz' => null,
        ]);
        $this->assertSame(['qux1' => 'FOO-val', 'qux2' => [null, 'BAR-val']], $dto->bar);

        /** @psalm-suppress UnusedVariable */
        $dto->clear()->loadArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            // default throw mode = ThrowMode::NEVER, but overriden by bang ('!baz')
            // -> missing value makes the mapper fail and throw.
            // Mapper throws are converted treated as "missing value", so bar prop
            // is kept untoutched and keeps its default value (empty string)
        ]);
        $this->assertSame(false, $dto->isFilled('bar'));
        $this->assertSame($dto->getDefaultValues()['bar'], $dto->bar);
    }

    public function testMapFromNullInputThrowsIfStrictRequiredByTwoBangs(): void
    {
        $dtoClassWithMapperThatThrows = new class extends FullDto {
            #[MapFrom(['qux1' => 'foo', 'qux2' => ['!!baz', 'bar']])]
            #[Assert\NotNull] // guard not executed
            public ?array $bar = null;
        };

        $dto = $dtoClassWithMapperThatThrows::newFromArrayLoose([
            'foo' => 'FOO-val',
            'bar' => 'BAR-val',
            'baz' => null, // null value makes mapper fail in this case (!!baz), resulting in missing value
        ]);
        // Since the bar prop is not marked as filled, it is not further processed by the NotNull validator,
        // therefore no GuardException is thrown here.

        $this->assertSame(false, $dto->isFilled('bar'));
    }

    public function testMapFromThrowsIfUsedInOutboundPhase(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The MapFrom attribute cannot be used in the outbound phase.');

        $dtoClass = new class extends FullDto {
            #[Outbound]
            #[MapFrom('foo')]
            public string | array | null $bar = '';
        };
        $dtoClass::newFromArrayLoose([
            'foo' => 'FOO-val',
        ]);
    }

    public function testMapTo(): void
    {
        $dtoClass = new class extends FullDto {
            #[MapTo('foo')]
            public string | array | null $bar = '';

            #[MapTo(null)]
            public ?string $notExported = null;
        };
        $dto = $dtoClass::newFromArray(['bar' => 'BAR-val', 'notExported' => 'NOT-exported-val']);
        $out = $dto->toOutboundArray();

        $this->assertSame(['foo' => 'BAR-val'], $out);
    }

    public function testMapFromFailsWithInvalidPath(): void
    {
        // Extract with invalid path input
        try {
            new MapFrom('foo.-bar!');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError::class, $e);
            $this->assertStringContainsString('Invalid path provided', $e->getMessage());
        }
    }
}
