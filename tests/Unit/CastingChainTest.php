<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use DateTimeImmutable;
use Mockery;
use Nandan108\DtoToolkit\Attribute\CastModifier\PerItem;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Cast;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use Nandan108\DtoToolkit\Core\CastBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;


final class CastingChainTest extends TestCase
{
    public function test_applies_all_caster_in_a_chain(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[Prefix('foo:'), Prefix('bar:'), Prefix('baz:')]
            public ?string $val=null; // default value provided for the example
        };

        $dto->fill(['val' => 'initial-value'])->normalizeInbound();


        $this->assertSame(
            'baz:bar:foo:initial-value',
            $dto->val,
        );
    }
    public function test_applies_chain_casting_and_perItem_modifier(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\ArrayFromCsv('/')] // split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            /**/#[CastTo\Trimmed('X ')] // trim whitespace
            /**/#[CastTo\Rounded(2 )] // round to 2 decimals
            /**/#[Prefix('$')] // add prefix (implicit cast to string)
            #[castTo\CsvFromArray(', ')] // (default separator is ',')
            public null|string|array $prices=null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --'])
            ->normalizeInbound();

        $this->assertSame(
            '$6.2, $0.99, $2, $3.5, $4.57',
            $dto->prices,
        );
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Prefix extends CastBase {
    public static int $counter = 0;
    public function __construct(public ?string $prefix = null, bool $outbound = false)
    {
        $this->prefix ??= (string)static::$counter++;
        parent::__construct($outbound, [$prefix]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        [$prefix] = $args;

        return "$prefix$value";
    }
};
