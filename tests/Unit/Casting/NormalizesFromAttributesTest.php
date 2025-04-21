<?php

namespace Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

final class NormalizesFromAttributesTest extends TestCase
{
    public function testReturnsNormalizedProperties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;

            #[CastTo\IfNull(-1)]
            #[CastTo\Integer]
            public string|int|null $age = null;
        };

        // Case 1: Assert that properties that are not "filled" are not normalized
        $dto->age = '30';
        $dto->normalizeInbound();
        /** @psalm-suppress RedundantCondition */
        $this->assertSame('30', $dto->age);

        // Case 2: Assert that properties that are "filled" are normalized
        $dto->fill(['age' => '30'])->normalizeInbound();
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame(30, $dto->age);
    }

    public function testNormalizeOutboundAppliesCastsToTaggedProperties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;

            #[CastTo\Trimmed(outbound: true)]
            public ?string $title = null;

            #[CastTo\Str(outbound: true)]
            public int|string|null $categoryId = null;

            #[CastTo\Str(outbound: true)]
            public int|string|null $foo = null;

            #[CastTo\Str(outbound: true)]
            public int|string|null $emptyString = null;

            #[CastTo\Str(outbound: true)]
            private int|string|null $privatePropWithSetter = null;

            public function setPrivatePropWithSetter(string $value): void
            {
                $this->privatePropWithSetter = $value;
            }

            public ?string $untouched = null;
        };

        $fooPrinter = new class {
            public function __toString(): string
            {
                return 'foo';
            }
        };

        $normalized = $dto->normalizeOutbound([
            'title'                 => '  Hello  ',
            'categoryId'            => 42,
            'untouched'             => 'value',
            'privatePropWithSetter' => 'val',
            'foo'                   => $fooPrinter,
            'emptyString'           => '',
        ]);

        $this->assertSame($normalized, [
            'title'                 => 'Hello',
            'categoryId'            => '42',
            'untouched'             => 'value',
            'privatePropWithSetter' => 'val',
            'foo'                   => 'foo',
            'emptyString'           => '',
        ]);
    }

    public function testGetCasterThrowsWhenMethodMissing(): void
    {
        $dto = new class extends BaseDto {
            // Note: no castToSomething method defined
        };

        $cast = new CastTo('FakeClassOrMethod');

        $this->expectException(CastingException::class);
        $this->expectExceptionMessage("Caster 'FakeClassOrMethod' could not be resolved");

        $cast->getCaster($dto);
    }
}
