<?php

namespace Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\CastModifier\PerItem;
use Nandan108\DtoToolkit\Cast;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

final class CastingChainTest extends TestCase
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

    public function testFailsIfPerItemIsAppliedOnANonArrayValue(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            // #[CastTo\ArrayFromCsv('/')] // FORGET to split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            #[Prefix('$')] // add prefix (implicit cast to string)
            #[CastTo\CsvFromArray(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            $this->fail('Expected CastingException not thrown');
        } catch (CastingException $e) {
            $this->assertStringStartsWith('PerItem modifier expected an array value, received string', $e->getMessage());
            $this->assertSame(3, $e->args['count']);
        }
    }

    public function testThrowsIfModifierCountArgIsGreaterThanFollowingCasterCount(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\ArrayFromCsv('/')] // split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\CsvFromArray(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            dump($dto);
            $this->fail('Expected CastingException not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('PerItem requested a subchain of 3 casters but only got 2', $e->getMessage());
        }
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Prefix extends CastBase
{
    public static int $counter = 0;

    public function __construct(public ?string $prefix = null, bool $outbound = false)
    {
        $this->prefix ??= (string) static::$counter++;
        parent::__construct($outbound, [$prefix]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        [$prefix] = $args;

        return "$prefix$value";
    }
}
