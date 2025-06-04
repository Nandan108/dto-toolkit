<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\CastTo\RegexReplace;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CastingChainTest extends TestCase
{
    public function testAppliesAllCasterInAChain(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[RegexReplace('/^/', 'foo:')]
            #[RegexReplace('/^/', 'bar:')]
            #[RegexReplace('/^/', 'baz:')]
            public ?string $val = null; // default value provided for the example
        };

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['val' => 'initial-value'])->normalizeInbound();

        $this->assertSame(
            'baz:bar:foo:initial-value',
            $dto->val,
        );
    }

    public function testAppliesChainCastingAndPerItemModifier(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\Split('/')] // split into an array
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[CastTo\Rounded(2)] // round to 2 decimals
            #[RegexReplace('/^/', '$')] // add prefix (implicit cast to string)
            #[CastTo\Join(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        /** @psalm-suppress UnusedMethodCall */
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
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            // #[CastTo\Split('/')] // FORGET to split into an array
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            /* - */ #[CastTo\Trimmed('X ')] // trim whitespace
            /* - */ #[CastTo\Rounded(2)] // round to 2 decimals
            /* - */ #[RegexReplace('/^/', '$')] // add $ prefix (implicit cast to string)
            #[CastTo\Join(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            $this->fail('Expected CastingException not thrown');
        } catch (CastingException $e) {
            $this->assertStringStartsWith('Prop `prices`: PerItem modifier expected an array value, received string', $e->getMessage());
            $this->assertSame(3, $e->args['count']);
        }
    }

    #[DataProvider('chainingModifiersProvider')]
    public function testChainingModifiers(string $someJson, string $expected): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[Mod\PerItem]
            /* - */ #[Mod\FailNextTo('n/a', count: 1)]
            /* ----- */ #[CastTo\Integer]
            #[CastTo\Join('/')]
            public array|string|int|null $someProp = null;
        };

        $someProp = json_decode($someJson);

        $dto->fill(['someProp' => $someProp]);
        $dto->normalizeInbound();

        $this->assertSame($expected, $dto->someProp);
    }

    public static function chainingModifiersProvider(): array
    {
        return [
            'test1' => [
                'someJson' => '["1", "008", "4", false]',
                'expected' => '1/8/4/0',
            ],
            'test2' => [
                'someJson' => '["-1", null, true]',
                'expected' => '-1/n/a/1',
            ],
            'test3' => [
                'someJson' => '["-1", "", 0]',
                'expected' => '-1/n/a/0',
            ],
        ];
    }

    public function testThrowsIfModifierCountArgIsGreaterThanFollowingCasterCount(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\Split('/')] // split into an array
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[Mod\FailNextTo('N/A'), CastTo\Rounded(2)] // round to 2 decimals
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\Join(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            $this->fail('Expected CastingException not thrown');
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('#[PerItem] expected 3 child nodes, but found only 2: [Trimmed, FailNextTo]', $msg);
        }

        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\Join(', ')] // (default separator is ',')
            public string|array|null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->normalizeInbound();
            $this->fail('Expected CastingException not thrown');
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('#[PerItem] expected 3 child nodes, but found none', $msg);
        }
    }
}
