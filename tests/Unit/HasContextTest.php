<?php

namespace Tests\Unit\Traits;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Traits\HasContext;
use PHPUnit\Framework\TestCase;

final class HasContextTest extends TestCase
{
    private ?object $object = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new class implements HasContextInterface {
            use HasContext;
        };
    }

    public function testSetContextAndGetContext(): void
    {
        $this->object or throw new \Exception('Object not initialized');

        // set a value 'foo' => 'bar' in the context
        $this->object->setContext('foo', 'bar');
        // check if the value is set
        $this->assertSame('bar', $this->object->getContext('foo'));
        // check if getting an unset value returns null
        $this->assertNull($this->object->getContext('missing'));
        // check if getting an unset value with a default returns the default
        $this->assertSame('default', $this->object->getContext('missing', 'default'));
        // Set a null value
        $this->object->setContext('foo', null);
        // Check if hasContext returns false for null value
        $this->assertFalse($this->object->hasContext('foo'));
        // Check if hasContext returns true for null value with treatNullAsMissing = false
        $this->assertTrue($this->object->hasContext('foo', false));
        // Remove value from context
        $this->object->unsetContext('foo');
        // Check if the value is removed -- hasContext with treatNullAsMissing = false should return false
        $this->assertFalse($this->object->hasContext('foo', false));
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
