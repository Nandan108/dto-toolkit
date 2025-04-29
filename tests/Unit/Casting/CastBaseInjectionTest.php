<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Injected;
use Nandan108\DtoToolkit\Bridge\ContainerBridge;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

// Dummy service to inject
final class DummySlugger
{
    public function slugify(string $text, string $separator = '-'): string
    {
        return strtolower(str_replace(' ', $separator, trim($text)));
    }
}

// Caster using #[Injected]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class InjectedSlugifyCasterResolvesWithContainer extends CastBase implements Injectable, Bootable
{
    public string $separator = '-';

    #[\Override]
    public function boot(): void
    {
        $this->separator = '*';
    }

    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Injected]
    private DummySlugger $slugger;

    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        return $this->slugger->slugify((string) $value, $this->separator);
    }

    #[\Override]
    protected function resolveFromContainer(string $type): mixed
    {
        if (DummySlugger::class === $type) {
            return new DummySlugger();
        }
        throw new \RuntimeException("Unknown type: $type");
    }
}

/** @psalm-suppress PropertyNotSetInConstructor */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class BridgeBasedSlugifyCaster extends CastBase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Injected] private DummySlugger $slugger;
    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        return $this->slugger->slugify((string) $value);
    }

    #[\Override]
    protected function resolveFromContainer(string $type): mixed
    {
        return ContainerBridge::get($type);
    }
}

final class FooBarDto extends FullDto
{
    #[CastTo(InjectedSlugifyCasterResolvesWithContainer::class)]
    public mixed $value = null;
}

final class CastBaseInjectionTest extends TestCase
{
    public function testInjectionAndCasting(): void
    {
        $dto = FooBarDto::fromArray(['value' => ' Hello World ']);

        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['value' => ' Hello World ']);

        $this->assertEquals('hello*world', $dto->value);
    }

    public function testBridgeBasedInjection(): void
    {
        /** @var ContainerInterface&MockObject $mockContainer */
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')
            ->with(DummySlugger::class)
            ->willReturn(new DummySlugger());
        $mockContainer->method('has')
            ->with(DummySlugger::class)
            ->willReturn(true);

        ContainerBridge::setContainer($mockContainer);

        $this->assertTrue(ContainerBridge::has(DummySlugger::class));

        $caster = new BridgeBasedSlugifyCaster();
        $caster->inject();

        $result = $caster->cast(' More DI Magic ', args: [], dto: new FooBarDto());
        $this->assertEquals('more-di-magic', $result);
    }

    public function testResolveFromContainerThrowsByDefault(): void
    {
        $caster = new class extends CastBase {
            /** @psalm-suppress PropertyNotSetInConstructor */
            #[Injected] private DummySlugger $slugger;
            /**
             * Dummy cast() method that's never called in this test.
             */
            #[\Override]
            public function cast(mixed $value, array $args, BaseDto $dto): mixed
            {
                return $this->slugger->slugify((string) $value);
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No container resolver defined in Core');

        $caster->inject();
    }

    public function testThrowsIfInjectedPropHasNoType(): void
    {
        $caster = new class extends CastBase {
            /** @psalm-suppress MissingPropertyType */
            #[Injected] private $prop;
            #[\Override]
            public function cast(mixed $value, array $args, BaseDto $dto): mixed
            {
                return $value;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot inject untyped property prop');

        $caster->inject();
    }

    public function testThrowsOnMethodCastingWithEmptyMethodName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No casting method name or class provided');

        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            #[CastTo('')]
            public mixed $value = null;
        };

        $dto->fill(['value' => 'foo']);
        $dto->normalizeInbound();
    }

    public function testInstantiatesWithConstructorArgs(): void
    {
        $casterClass = new class('X') extends CastBase {
            public function __construct(public string $prefix)
            {
            }

            #[\Override]
            public function cast(mixed $value, array $args, BaseDto $dto): mixed
            {
                return $this->prefix.$value;
            }
        };

        $attr = new CastTo(get_class($casterClass), args: [], constructorArgs: ['X']);
        $dto = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('Xfoo', $caster('foo'));
    }
}
