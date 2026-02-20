<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Support;

use Nandan108\DtoToolkit\Support\CacheKeySerializer;
use PHPUnit\Framework\TestCase;

final class CacheKeySerializerTest extends TestCase
{
    public function testSerializeReturnsFallbackWhenJsonEncodingFails(): void
    {
        $serialized = CacheKeySerializer::serialize(NAN);
        $this->assertSame('[]', $serialized);
    }

    public function testSerializeNormalScalarValue(): void
    {
        $serialized = CacheKeySerializer::serialize('ok');
        $this->assertSame('"ok"', $serialized);
    }
}
