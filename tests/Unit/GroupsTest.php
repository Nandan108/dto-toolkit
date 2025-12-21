<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier\Groups;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\PropGroups;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class GroupsTestFooBarDto extends FullDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    #[CastTo\Lowercase] // trim dashes
    public ?string $qux = null; // default value provided for the example

    /** @psalm-suppress PossiblyUnusedProperty */
    #[PropGroups('foo')]
    #[CastTo\Trimmed('-')] // trim dashes
    public ?string $foo = null; // default value provided for the example

    /** @psalm-suppress PossiblyUnusedProperty */
    #[PropGroups('bar')]
    #[CastTo\Lowercase]
    public ?string $bar = null; // default value provided for the example

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Outbound]
    #[CastTo\Uppercase]
    #[PropGroups('bar')]
    public ?string $baz = null; // default value provided for the example
}

final class CasterGroupsTestDto extends FullDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $notCast = null; // default value provided for the example

    /** @psalm-suppress PossiblyUnusedProperty */
    #[CastTo\Trimmed('- ')] // trim dashes
    // if in group 'foo', make it uppercase and add 'foo:' prefix
    #[Groups('bar'), CastTo\RegexReplace('/^/', 'Bar:')]
    #[Groups('foo', 2), CastTo\Uppercase, CastTo\RegexReplace('/^/', 'Foo:')]
    #[Outbound]
    #[CastTo\Uppercase]
    public ?string $baz = null; // default value provided for the example
}

final class NotImplmementingUsesGroupsDto extends BaseDto implements ProcessesInterface
{
    use ProcessesFromAttributes;
    /** @psalm-suppress PossiblyUnusedProperty */
    #[Groups('foo')]
    #[CastTo\Lowercase]
    public ?string $qux = null;
}

final class GroupsTest extends TestCase
{
    public function testOutboundGroupsExcludePropertiesWithoutMatchingContext(): void
    {
        $input = [
            'baz' => 'Test Value',
        ];

        /** @psalm-suppress UndefinedMagicMethod */
        $dto = GroupsTestFooBarDto::withGroups(inbound: 'foo')->fromArray($input);

        $output = $dto->toOutboundArray();

        $this->assertArrayNotHasKey('baz', $output, 'Property "baz" should not be exported if not in scope');
        $this->assertSame([], $output, 'Output array should be empty if no filled fields are in scope');
    }

    public function testAppliesPropGroups(): void
    {
        $input = [
            'qux' => 'Qux',
            'foo' => '-foo--',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ];
        /** @psalm-suppress UndefinedMagicMethod */
        $arr = GroupsTestFooBarDto::withGroups('foo')->fromArray($input)->toOutboundArray();
        // Baz is note included because it's in PropGroups('bar'), which isn't targetted
        $this->assertSame(['qux' => 'qux', 'foo' => 'foo'], $arr);

        /** @psalm-suppress UndefinedMagicMethod */
        $arr = GroupsTestFooBarDto::withGroups('bar')->fromArray($input)->toOutboundArray();
        // This time, it's foo that's missing.
        $this->assertSame(['qux' => 'qux', 'bar' => 'bar', 'baz' => 'BAZ'], $arr);
    }

    public function testCasterGroupsApplyConditionally(): void
    {
        $input = [
            'notCast' => '- hello ',
            'baz'     => '- world ',
        ];

        // With no groups activated
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = CasterGroupsTestDto::fromArray($input);
        $this->assertSame(['notCast' => '- hello ', 'baz' => 'world'], $dto->toarray());

        // With group 'foo' active inbound
        /** @psalm-suppress UndefinedMagicMethod */
        $dtoFoo = CasterGroupsTestDto::withGroups(inbound: 'foo')->fromArray($input);
        $this->assertSame('- hello ', $dtoFoo->notCast, 'notCast is not affected by groups');
        $this->assertSame('Foo:WORLD', $dtoFoo->baz, 'baz is trimmed, uppercased and prefixed Foo:');

        // With group 'bar' active inbound
        /** @psalm-suppress UndefinedMagicMethod */
        $dtoBar = CasterGroupsTestDto::withGroups(inbound: 'bar')->fromArray($input);
        $this->assertSame('Bar:world', $dtoBar->baz, 'baz is trimmed and prefixed Bar:');

        // With both 'bar' amd 'foo' active inbound
        /** @psalm-suppress UndefinedMagicMethod */
        $dtoBar = CasterGroupsTestDto::withGroups(inbound: ['foo', 'bar'])->fromArray($input);
        $this->assertSame('Foo:BAR:WORLD', $dtoBar->baz, 'baz is trimmed and prefixed Bar:');
    }

    public function testOutboundUppercaseIsAlwaysAppliedToBaz(): void
    {
        $input = [
            'baz' => '- sample ',
        ];

        $dto = CasterGroupsTestDto::fromArray($input);
        $output = $dto->toOutboundArray();

        $this->assertSame('SAMPLE', $output['baz'], 'baz should be uppercased at export');
    }

    public function testNormalizationThrowsIfGroupsAreUsedWithoutImplmementingUsesGroups(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('To use #[Groups], DTO must use UsesGroups trait or implement HasGroupsInterface');

        $dto = new NotImplmementingUsesGroupsDto();
        $dto->processInbound();
    }
}
