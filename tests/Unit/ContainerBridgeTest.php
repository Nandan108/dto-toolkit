<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Mockery\Container;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerBridgeTest extends TestCase
{
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
        ContainerBridge::register('ClosureTest', fn () => new \stdClass());
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
        $this->expectException(\LogicException::class);
        ContainerBridge::get('NonExistentClassOrBinding');
    }

    public function testDelegatesToContainerIfSet(): void
    {
        /** @var ContainerInterface|\Mockery\MockInterface $mock */
        $mock = $this->createMock(ContainerInterface::class);
        $mock->expects($this->once())
             ->method('get')
             ->with('Service')
             ->willReturn(new \stdClass());

        ContainerBridge::setContainer($mock);
        $this->assertInstanceOf(\stdClass::class, ContainerBridge::get('Service'));
    }
}
