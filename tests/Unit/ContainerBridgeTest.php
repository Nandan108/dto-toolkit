<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Mockery\Container;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ContainerBridgeTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        // Reset container and bindings after each test
        ContainerBridge::setContainer(null);
        ContainerBridge::clearBindings();
    }

    public function testRegistersAndReturnsSingletonObject(): void
    {
        $obj = new \stdClass();
        ContainerBridge::register('SingletonTest', $obj);
        $this->assertSame($obj, ContainerBridge::get('SingletonTest'));
    }

    public function testRegistersAndReturnsFromClosure(): void
    {
        $this->assertFalse(ContainerBridge::has('ClosureTest'));
        ContainerBridge::register('ClosureTest', fn () => new \stdClass());
        $this->assertTrue(ContainerBridge::has('ClosureTest'));

        $a = ContainerBridge::get('ClosureTest');
        $b = ContainerBridge::get('ClosureTest');
        $this->assertInstanceOf(\stdClass::class, $a);
        $this->assertNotSame($a, $b, 'Closure should return a new instance each time');
    }

    public function testRegistersAndInstantiatesClass(): void
    {
        ContainerBridge::register('ClassTest', \stdClass::class);
        $this->assertInstanceOf(\stdClass::class, ContainerBridge::get('ClassTest'));
    }

    public function testThrowsWhenUnresolvable(): void
    {
        $this->expectException(InvalidConfigException::class);
        ContainerBridge::get('NonExistentClassOrBinding');
    }

    public function testDelegatesToContainerIfSet(): void
    {
        /** @var ContainerInterface|\Mockery\MockInterface $mock */
        /** @psalm-suppress PossiblyUndefinedMethod */
        $mock = $this->createMock(ContainerInterface::class);
        $mock->expects($this->once())
             ->method('has')
             ->with('Service')
             ->willReturn(true);
        $mock->expects($this->once())
             ->method('get')
             ->with('Service')
             ->willReturn(new \stdClass());

        ContainerBridge::setContainer($mock);
        $service = ContainerBridge::get('Service');
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testDelegatesToMappedConcreteInContainerWhenAbstractIsMissing(): void
    {
        /** @var ContainerInterface|\Mockery\MockInterface $mock */
        $mock = $this->createMock(ContainerInterface::class);
        /** @psalm-suppress PossiblyUndefinedMethod */
        $mock->expects($this->exactly(2))
            ->method('has')
            ->willReturnCallback(
                fn (string $id): bool => 'AliasTarget' === $id,
            );
        /** @psalm-suppress PossiblyUndefinedMethod */
        $mock->expects($this->once())
            ->method('get')
            ->with('AliasTarget')
            ->willReturn(new \stdClass());

        ContainerBridge::register('AliasService', 'AliasTarget');
        /** @var ContainerInterface $mock */
        ContainerBridge::setContainer($mock);

        $service = ContainerBridge::get('AliasService');
        $this->assertInstanceOf(\stdClass::class, $service);
    }
}
