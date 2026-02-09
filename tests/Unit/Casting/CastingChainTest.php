<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\CastTo\RegexReplace;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CastingChainTest extends TestCase
{
    public function testAppliesAllCasterInAChain(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[RegexReplace('/^/', 'foo:')]
            #[RegexReplace('/^/', 'bar:')]
            #[RegexReplace('/^/', 'baz:')]
            public ?string $val = null; // default value provided for the example
        };

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['val' => 'initial-value'])->processInbound();

        $this->assertSame(
            'baz:bar:foo:initial-value',
            $dto->val,
        );
    }

    public function testAppliesChainCastingAndPerItemModifier(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\Split('/')] // split into an array
            // Apply next 4 casters on the value's array elements instead of whole value
            #[Mod\PerItem(4),
                CastTo\Trimmed('X '),
                CastTo\Rounded(2),
                Assert\Range(2, 7),
                RegexReplace('/^/', '$'),
            ]
            #[CastTo\Join(', ')] // (default separator is ',')
            public string | array | null $prices = null; // default value provided for the example
        };

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['prices' => '---  X 6.196/  1.996/X2.00001/XX 3.5  /XX4.57   --'])
            ->processInbound();

        $this->assertSame(
            '$6.2, $2, $2, $3.5, $4.57',
            $dto->prices,
        );

        try {
            $dto->fill(['prices' => '--- 7.001 / X 7.196/  0.99--'])
                ->processInbound();
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('processing.guard.invalid_value.number.above_max', $e->getMessageTemplate());
            $this->assertSame(
                expected: 'prices{CastTo\Trimmed->CastTo\Split->Mod\PerItem}[1]{CastTo\Trimmed->CastTo\Rounded->Assert\Range}',
                actual: $e->getPropertyPath(),
            );
        }
    }

    public function testFailsIfPerItemIsAppliedOnANonArrayValue(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            // #[CastTo\Split('/')] // FORGET to split into an array
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            /* - */ #[CastTo\Trimmed('X ')] // trim whitespace
            /* - */ #[CastTo\Rounded(2)] // round to 2 decimals
            /* - */ #[RegexReplace('/^/', '$')] // add $ prefix (implicit cast to string)
            #[CastTo\Join(', ')] // (default separator is ',')
            public string | array | null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->processInbound();
            $this->fail('Expected TransformException not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('processing.modifier.per_item.expected_array', $e->getMessageTemplate());
            $this->assertSame('prices{CastTo\Trimmed->Mod\PerItem}', $e->getPropertyPath());
        }
    }

    #[DataProvider('chainingModifiersProvider')]
    public function testChainingModifiers(string $someJson, string $expected): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\PerItem]
            /* - */ #[Mod\FailNextTo('n/a', count: 1)]
            /* ----- */ #[CastTo\Integer]
            #[CastTo\Join('/')]
            public array | string | int | null $someProp = null;
        };

        $someProp = json_decode($someJson);

        $dto->fill(['someProp' => $someProp]);
        $dto->processInbound();

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
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[CastTo\Split('/')] // split into an array
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            #[CastTo\Trimmed('X ')] // trim whitespace
            #[Mod\FailNextTo('N/A'), CastTo\Rounded(2)] // round to 2 decimals
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\Join(', ')] // (default separator is ',')
            public string | array | null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->processInbound();
            $this->fail('Expected TransformException not thrown');
        } catch (InvalidConfigException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('#[Mod\PerItem] expected 3 child nodes, but found only 2: [CastTo\Trimmed, Mod\FailNextTo]', $msg);
        }

        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo\Trimmed('-')] // trim dashes
            #[Mod\PerItem(3)] // Apply next 3 casters on the value's array elements instead of whole value
            // #[Prefix('$')] // add prefix (implicit cast to string)
            // #[CastTo\Join(', ')] // (default separator is ',')
            public string | array | null $prices = null; // default value provided for the example
        };

        $dto->fill(['prices' => '---  X 6.196/  0.99/X2.00001/XX 3.5  /XX4.57   --']);
        try {
            $dto->processInbound();
            $this->fail('Expected TransformException not thrown');
        } catch (InvalidConfigException $e) {
            $msg = $e->getMessage();
            $this->assertStringStartsWith('#[Mod\PerItem] expected 3 child nodes, but found none', $msg);
        }
    }
}
