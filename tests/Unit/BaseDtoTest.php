<?php

namespace Tests\Unit\Dto;

use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesInbound;
use PHPUnit\Framework\TestCase;
use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;
use Nandan108\SymfonyDtoToolkit\Contracts\CasterInterface;
use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesOutbound;
use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Traits\NormalizesFromAttributes;
use PhpParser\Node\Scalar\MagicConst\Dir;


class BaseDtoTest extends TestCase
{
    public function test_returns_normalized_properties(): void
    {
        $dto = new class extends BaseDto
            // implements NormalizesInbound
        {
            use NormalizesFromAttributes;

            #[CastTo('intOrNull')]
            public string|int|null $age;
        };


        // Case 1: Assert that properties that are not "filled" are not normalized
        $dto->age = "30";
        $dto->normalizeInbound();
        $this->assertSame("30", $dto->age);

        // Case 2: Null input
        $dto->age = null;
        $dto->filled['age'] = true;
        $dto->normalizeInbound();
        $this->assertNull($dto->age);

        // Case 3: Assert that properties that are "filled" are normalized
        $dto->filled['age'] = true;
        $dto->age = "30";
        $dto->normalizeInbound();
        $this->assertSame(30, $dto->age);

        // Case 4: Assert that invalid values are set to null
        $dto->age = 'not-a-number';
        $dto->normalizeInbound();
        $this->assertNull($dto->age);
    }

    // public function test_handles_missing_optional_properties(): void {}
    // public function test_supports_get_entity_setter_map(): void {}
}
