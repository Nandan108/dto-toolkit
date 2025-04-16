<?php

declare(strict_types=1);

namespace Tests\Cast;

use Nandan108\DtoToolkit\Attribute\Injected;
use Nandan108\DtoToolkit\Bridge\ContainerBridge;
use Nandan108\DtoToolkit\Core\CastBase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

// Dummy service to inject
final class DummySlugger
{
    public function slugify(string $text): string
    {
        return strtolower(str_replace(' ', '-', trim($text)));
    }
}

// Caster using #[Injected]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SlugifyCaster extends CastBase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Injected] private DummySlugger $slugger;


    #[\Override]
    public function cast(mixed $value, array $args = []): string
    {
        return $this->slugger->slugify((string) $value);
    }

    #[\Override]
    protected function resolveFromContainer(string $type): mixed
    {
        if ($type === DummySlugger::class) {
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
    public function cast(mixed $value, array $args = []): string
    {
        return $this->slugger->slugify((string) $value);
    }

    #[\Override]
    protected function resolveFromContainer(string $type): mixed
    {
        return ContainerBridge::get($type);
    }
}

final class CastBaseInjectionTest extends TestCase
{
    public function testInjectionAndCasting(): void
    {
        $caster = new SlugifyCaster();
        $caster->inject();

        $result = $caster->cast(' Hello World ');
        $this->assertEquals('hello-world', $result);
    }

    public function testBridgeBasedInjection(): void
    {
        /** @var ContainerInterface & MockObject $mockContainer */
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

        $result = $caster->cast(' More DI Magic ');
        $this->assertEquals('more-di-magic', $result);
    }

    public function testResolveFromContainerThrowsByDefault(): void
    {
        $caster = new class extends CastBase {
            /** @psalm-suppress PropertyNotSetInConstructor */
            #[Injected] private DummySlugger $slugger;

            /**
             * Dummy cast() method that's never called in this test
             * @return mixed
             */
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed {
                return $this->slugger->slugify((string) $value);
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No container resolver defined in Core");

        $caster->inject();
    }
}
