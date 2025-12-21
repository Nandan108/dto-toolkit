<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
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

final class DummySluggerWithCtorArgs
{
    public function __construct(public string $foo)
    {
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
    #[Inject]
    private DummySlugger $slugger;

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return $this->slugger->slugify((string) $value, $this->separator);
    }
}

/** @psalm-suppress PropertyNotSetInConstructor */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class BridgeBasedSlugifyCaster extends CastBase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    #[Inject] private DummySlugger $slugger;

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return $this->slugger->slugify((string) $value);
    }
}

final class FooBarDto extends FullDto
{
    #[CastTo(InjectedSlugifyCasterResolvesWithContainer::class)]
    public mixed $value = null;
}

final class InjectIntoCastBaseTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        // Reset container and bindings after each test
        ContainerBridge::setContainer(null);
        ContainerBridge::clearBindings();
    }

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

        $result = $caster->cast(' More DI Magic ', args: []);
        $this->assertEquals('more-di-magic', $result);
    }

    public function testThrowsIfInjectedPropHasNoType(): void
    {
        $caster = new class extends CastBase {
            /** @psalm-suppress MissingPropertyType */
            #[Inject] private $prop;

            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return $value;
            }
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Cannot inject untyped property prop');

        $caster->inject();
    }

    public function testThrowsOnMethodCastingWithEmptyMethodName(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No method name or class provided');

        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo('')]
            public mixed $value = null;
        };

        $dto->fill(['value' => 'foo']);
        $dto->processInbound();
    }

    public function testInstantiatesWithConstructorArgs(): void
    {
        $casterClass = new class('X') extends CastBase {
            public function __construct(public string $prefix)
            {
            }

            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return $this->prefix.$value;
            }
        };

        $attr = new CastTo(get_class($casterClass), args: [], constructorArgs: ['X']);
        $dto = new class extends BaseDto {};
        $caster = $attr->getProcessingNode($dto);
        $this->assertSame('Xfoo', $caster('foo'));
    }
}
