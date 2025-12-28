<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\ChainModifier\Groups;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class WithDefaultGroupsTest extends TestCase
{
    public function testWithDefaultGroupsSetsUp(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = WithGroupsDto::newInstance();

        $this->assertSame($dto->getActiveGroups(Phase::InboundLoad), ['bar']);
        $this->assertSame($dto->getActiveGroups(Phase::InboundCast), ['baz']);
        $this->assertSame($dto->getActiveGroups(Phase::OutboundCast), ['foo']);
        $this->assertSame($dto->getActiveGroups(Phase::OutboundExport), ['foo']);
    }

    public function testUsingWithDefaultGroupsOnClassNotImplementingHasGroupsInterfaceThrowsException(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The WithDefaultGroups attribute can only be used on DTOs that implement the HasGroupsInterface');

        ClassNotImplementingHasGroupsInterface::newInstance();
    }

    public function testPropertyWithGroupsAttributesAreAppliedCorrectly(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = WithGroupsDto::newInstance();

        /** @psalm-suppress UndefinedMagicMethod */
        $dto->_withGroups('bar')->fromArray(['someProp' => 'some value']);

        // Inbound phase: only 'bar' group is active, so SnakeCase caster is applied
        $this->assertSame('some_value', $dto->someProp);

        // Outbound phase: only 'foo' group is active, so prefix_ is added
        /** @psalm-suppress UndefinedMagicMethod */
        $exported = $dto->toOutboundArray();
        $this->assertSame('prefix_some_value', $exported['someProp']);
    }
}

#[WithDefaultGroups(
    all: 'foo',
    inbound: 'bar',
    inboundCast: 'baz',
    // outbound: defaults to 'foo'
    // outboundCast: defaults to 'foo'
)]
final class WithGroupsDto extends FullDto
{
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Groups('foo'), CastTo\PascalCase]
    #[Groups('bar'), CastTo\SnakeCase]
    #[Outbound]
    #[Groups('bar'), CastTo\RegexReplace('/^/', 'prefix_')]
    public ?string $someProp = null;
}

#[WithDefaultGroups('foo')]
final class ClassNotImplementingHasGroupsInterface extends BaseDto
{
}
