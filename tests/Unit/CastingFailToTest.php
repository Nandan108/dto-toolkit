<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\CastModifier\PerItem;
use Nandan108\DtoToolkit\Cast;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

final class CastingFailToTest extends TestCase
{
    public function testAppliesAllCasterInAChain(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[Prefix('foo:'), Prefix('bar:'), Prefix('baz:')]
            public ?string $val = null; // default value provided for the example
        };

        $dto->fill(['val' => 'initial-value'])->normalizeInbound();

        $this->assertSame(
            'baz:bar:foo:initial-value',
            $dto->val,
        );
    }

    public function testAppliesChainCastingAndPerItemModifier(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\ArrayFromCsv('/')] // split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            #[Prefix('$')] // add prefix (implicit cast to string)
            #[CastTo\CsvFromArray(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --'])
            ->normalizeInbound();

        $this->assertSame(
            '$6.2, $0.99, $2, $3.5, $4.57',
            $dto->prices,
        );
    }
}
