<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Traits\HasContext;
use PHPUnit\Framework\TestCase;

final class HasContextTest extends TestCase
{
    private ?HasContextTest_Fixture $object = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new HasContextTest_Fixture();
    }

    public function testSetContextAndGetContext(): void
    {
        $this->object or throw new \Exception('Object not initialized');

        // set a value 'foo' => 'bar' in the context
        $this->object->contextSet('foo', 'bar');
        // check if the value is set
        $this->assertSame('bar', $this->object->contextGet('foo'));
        // check if getting an unset value returns null
        $this->assertNull($this->object->contextGet('missing'));
        // check if getting an unset value with a default returns the default
        $this->assertSame('default', $this->object->contextGet('missing', 'default'));
        // Set a null value
        $this->object->contextSet('foo', null);
        // Check if contextHas returns false for null value
        $this->assertFalse($this->object->contextHas('foo'));
        // Check if contextHas returns true for null value with treatNullAsMissing = false
        $this->assertTrue($this->object->contextHas('foo', false));
        // Remove value from context
        $this->object->contextUnset('foo');
        // Check if the value is removed -- contextHas with treatNullAsMissing = false should return false
        $this->assertFalse($this->object->contextHas('foo', false));
    }

    public function testWithContextAndGetContextMap(): void
    {
        $this->object or throw new \Exception('Object not initialized');

        $data = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];

        $this->object->withContext($data);

        $this->assertSame(1, $this->object->contextGet('a'));
        $this->assertSame(2, $this->object->contextGet('b'));
        $this->assertSame(3, $this->object->contextGet('c'));

        $this->assertSame($data, $this->object->getContext());
    }
}

final class HasContextTest_Fixture implements HasContextInterface
{
    use HasContext;
}
