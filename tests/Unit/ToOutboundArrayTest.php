<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class ToOutboundArrayTest extends TestCase
{
    public function testBaseDtoToExportsToOutboundArray(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto {
            public string|int|null $item_id = null;
            public string|int|null $staysUnfilled = 'yes';
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto->fill([ // GET
            'item_id' => $rawItemId = '5',
        ]);

        // still raw
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawItemId, $dto->item_id);

        // filled
        $this->assertArrayHasKey('item_id', $dto->_filled);
        // not filled
        $this->assertArrayNotHasKey('staysUnfilled', $dto->_filled);

        $output = $dto->toOutboundArray();

        $this->assertSame(
            [
                'item_id' => $rawItemId,
                // 'staysUnfilled' => 'yes', // not filled = not included
            ],
            $output,
        );
    }

    public function testBaseDtoToExportsToOutboundArrayAfterNormalizing(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInboundInterface, NormalizesOutboundInterface {
            use NormalizesFromAttributes;

            #[CastTo\Trimmed('-')] // inbound: trim dashes
            #[Outbound]
            #[CastTo\Integer()] // outbound: cast to int
            public ?string $num = null;

            #[CastTo\Integer]
            public ?int $multiplier = null;

            public ?string $staysUnfilled = 'yes';

            #[\Override]
            public function preOutput(mixed &$outputData): void
            {
                if (is_array($outputData)) {
                    // make some changes to the data before output
                    $outputData['num'] *= $outputData['multiplier'];
                    $outputData['setByHook'] = 'foo-Bar!';
                }
            }
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto->fill([ // GET
            'multiplier' => $multiplier = '2', // will be auto-cast to int due to target prop type
            'num'        => $rawItemId = '-5.7-',
        ]);

        // still raw
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawItemId, $dto->num);

        // filled
        $this->assertArrayHasKey('num', $dto->_filled);
        // not filled
        $this->assertArrayNotHasKey('staysUnfilled', $dto->_filled);

        $dto->normalizeInbound(); // runs normalization

        // now check $num value after inbound normalization
        $this->assertSame(trim($rawItemId, '-'), $dto->num);

        // runs normalization and exports array
        $output = $dto->toOutboundArray();

        // now check $output value after outbound casting and hook
        $this->assertSame(
            [
                'num'        => (int) trim($rawItemId, '-') * $multiplier,
                'multiplier' => 2,
                'setByHook'  => 'foo-Bar!',
            ],
            $output,
        );
    }
}
