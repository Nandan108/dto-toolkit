<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use DateTimeImmutable;
use Mockery;
use Nandan108\DtoToolkit\Core\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;


final class NormalizesFromAttributesTest extends TestCase
{
    public function test_returns_normalized_properties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;

            #[CastTo('intOrNull')]
            public null|string|int $age = null;
        };

        // Case 1: Assert that properties that are not "filled" are not normalized
        $dto->age = "30";
        $dto->normalizeInbound();
        /** @psalm-suppress RedundantCondition */
        $this->assertSame("30", $dto->age);

        // Case 2: Null input
        $dto->fill(['age' => null])->normalizeInbound();
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertNull($dto->age);

        // Case 3: Assert that properties that are "filled" are normalized
        $dto->fill(['age' => "30"])->normalizeInbound();
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame(30, $dto->age);

        // Case 4: Assert that invalid values are set to null
        $dto->fill(['age' => "not-a-number"])->normalizeInbound();
        $this->assertNull($dto->age);
    }

    public function test_normalize_outbound_applies_casts_to_tagged_properties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;

            #[CastTo('trimmedString', outbound: true)]
            public ?string $title = null;

            #[CastTo('stringOrNull', outbound: true)]
            public int|string|null $categoryId = null;

            #[CastTo('stringOrNull', outbound: true)]
            private int|string|null $privatePropWithSetter = null;
            public function setPrivatePropWithSetter(string $value): void
            {
                $this->privatePropWithSetter = $value;
            }

            public ?string $untouched = null;
        };

        $normalized = $dto->normalizeOutbound([
            'title'                 => '  Hello  ',
            'categoryId'            => 42,
            'untouched'             => 'value',
            'privatePropWithSetter' => 'val',
        ]);

        $this->assertSame('Hello', $normalized['title']);
        $this->assertSame('42', $normalized['categoryId']);
        $this->assertSame('value', $normalized['untouched']); // unchanged
        $this->assertSame('val', $normalized['privatePropWithSetter']);
    }

    public function test_get_caster_throws_when_method_missing(): void
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
