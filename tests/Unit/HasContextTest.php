<?php

namespace Tests\Unit\Traits;

use Nandan108\DtoToolkit\Traits\HasContext;
use PHPUnit\Framework\TestCase;

final class HasContextTest extends TestCase
{
    private ?object $object = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new class {
            use HasContext;
        };
    }

    public function testSetContextAndGetContext(): void
    {
        $this->object or throw new \Exception('Object not initialized');

        $this->object->setContext('foo', 'bar');

        $this->assertSame('bar', $this->object->getContext('foo'));
        $this->assertNull($this->object->getContext('missing'));
        $this->assertSame('default', $this->object->getContext('missing', 'default'));
    }

    public function testWithContextAndGetContextMap(): void
    {
        $this->object or throw new \Exception('Object not initialized');

        $data = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];

        $this->object->_withContext($data);

        $this->assertSame(1, $this->object->getContext('a'));
        $this->assertSame(2, $this->object->getContext('b'));
        $this->assertSame(3, $this->object->getContext('c'));

        $this->assertSame($data, $this->object->getContextMap());
    }
}
