<?php

namespace Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\CastModifier\FailNextTo;
use Nandan108\DtoToolkit\Attribute\CastModifier\PerItem;
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
            #[CastTo\Split('/')] // split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            #[Prefix('$')] // add prefix (implicit cast to string)
            #[CastTo\Join(', ')] // (default separator is ',')
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
            // #[CastTo\Split('/')] // FORGET to split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            /* - */ #[CastTo\Trimmed('X ')] // trim whitespace
            /* - */ #[CastTo\Rounded(2)] // round to 2 decimals
            /* - */ #[Prefix('$')] // add prefix (implicit cast to string)
            #[CastTo\Join(', ')] // (default separator is ',')
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

    /**
     * @testWith ["[\"1\", \"008\", \"4\", false]", "1/8/4/0"]
     *           ["[\"-1\", null, true]", "-1/n/a/1"]
     *           ["[\"-1\", \"\", 0]", "-1/n/a/0"]
     */
    public function testChainingModifiers($someJson, $expected): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[PerItem]
            /* - */ #[FailNextTo('n/a')]
            /* ----- */ #[CastTo\Integer]
            #[CastTo\Join('/')]
            public mixed $someProp;
        };

        $someProp = json_decode($someJson);

        $dto->fill(['someProp' => $someProp]);
        $dto->normalizeInbound();

        $this->assertSame($expected, $dto->someProp);
    }

    public function testThrowsIfModifierCountArgIsGreaterThanFollowingCasterCount(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\Split('/')] // split into an array
            #[PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\Join(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            $this->fail('Expected CastingException not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('PerItem requested 3 castable elements, but only found 2', $e->getMessage());
        }
        $this->assertTrue(true);
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Prefix extends CastBase
{
    public static int $counter = 0;

    public function __construct(public ?string $prefix = null)
    {
        $this->prefix ??= (string) static::$counter++;
        parent::__construct([$prefix]);
    }

    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        [$prefix] = $args;

        return "$prefix$value";
    }
}
