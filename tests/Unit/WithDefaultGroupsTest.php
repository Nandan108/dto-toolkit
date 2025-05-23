<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\ChainModifier\Groups;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\WithDefaultGroups;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
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
        $this->assertSame($dto->getActiveGroups(Phase::Validation), ['foo']);
    }

    public function testUsingWithDefaultGroupsOnClassNotImplementingHasGroupsInterfaceThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The WithDefaultGroups attribute can only be used on DTOs that implement the HasGroupsInterface');

        ClassNotImplementingHasGroupsInterface::newInstance();
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
    use NormalizesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Groups('foo'), CastTo\PascalCase]
    #[Groups('bar'), CastTo\SnakeCase]
    #[Outbound]
    #[Groups('bar'), CastTo\RegexReplace('/^/', 'prefix_')]
    public ?string $num = null;
}

#[WithDefaultGroups('foo')]
final class ClassNotImplementingHasGroupsInterface extends BaseDto
{
}
