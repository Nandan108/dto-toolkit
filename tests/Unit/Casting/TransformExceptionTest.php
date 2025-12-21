<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the new TransformException API.
 *
 * These tests use the "hybrid" strategy:
 * - Assert exception type
 * - Assert template key
 * - Assert parameter array values
 * - Assert debug formatting logic
 * - DO NOT assert final message text or translation output.
 */
final class TransformExceptionTest extends TestCase
{
    public function testExpectedBuildsCorrectTemplateAndParameters(): void
    {
        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\NumericCaster',
            operand: 'hello',
            expected: 'numeric',
        );

        $this->assertInstanceOf(TransformException::class, $ex);

        // Template
        $this->assertSame('processing.transform.expected', $ex->getMessageTemplate());

        // Parameters
        $params = $ex->getMessageParameters();
        $debug = $ex->getDebugInfo();
        $this->assertSame('numeric', $params['expected']);
        $this->assertSame('hello', $debug['orig_value']);
        $this->assertSame(json_encode('hello'), $debug['value']);
        $this->assertSame('string', $debug['type']);
        $this->assertSame('App\\CastTo\\NumericCaster', $params['methodOrClass']);
    }

    public function testReasonBuildsCorrectTemplateAndParameters(): void
    {
        $ex = TransformException::reason(
            methodOrClass: 'App\\CastTo\\DateCaster',
            value: '99-99-9999',
            template_suffix: 'date.invalid_format',
            parameters: ['format' => 'Y-m-d'],
        );

        $this->assertInstanceOf(TransformException::class, $ex);
        $this->assertSame('processing.transform.date.invalid_format', $ex->getMessageTemplate());

        $params = $ex->getMessageParameters();
        $this->assertSame('Y-m-d', $params['format']);

        // Debug
        $debug = $ex->getDebugInfo();
        $this->assertSame('99-99-9999', $debug['orig_value']);
        $this->assertSame('string', $debug['type']);
    }

    public function testOperandDebugForObjects(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';

        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\StringCaster',
            operand: $obj,
            expected: 'string',
        );

        // Debug
        $debug = $ex->getDebugInfo();
        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('foo', $debug['value']);
    }

    public function testOperandDebugForJsonSerializable(): void
    {
        $jsonObj = new JsonLike(['x' => 'y']);

        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\JsonCaster',
            operand: $jsonObj,
            expected: 'numeric',
        );

        $debug = $ex->getDebugInfo();

        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('"x":"y"', $debug['value']);
    }

    public function testOperandDebugForStringableObject(): void
    {
        $stringable = new StringableClass();

        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\ToStringCaster',
            operand: $stringable,
            expected: 'numeric',
        );

        $debug = $ex->getDebugInfo();

        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('stringified!', $debug['value']);
    }

    public function testOperandDebugForResource(): void
    {
        $resource = fopen('php://temp', 'r');

        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\ResourceCaster',
            operand: $resource,
            expected: 'string',
        );

        $resource && fclose($resource);

        $debug = $ex->getDebugInfo();
        $this->assertSame('resource (stream)', $debug['type']);
        $this->assertSame('[unrepresentable]', $debug['value']);
    }

    public function testDebugTruncation(): void
    {
        $long = str_repeat('A', 200);

        // temporarily reduce debug max length
        $orig = ProcessingException::$max_text_length;
        ProcessingException::$max_text_length = 30;

        $ex = TransformException::expected(
            methodOrClass: 'App\\CastTo\\Truncate',
            operand: $long,
            expected: 'numeric',
        );

        $debugValue = $ex->getDebugInfo()['value'];
        $debugValueLength = strlen($debugValue);
        $maxDebugValueLength = ProcessingException::$max_text_length + 3; // account for "..."

        ProcessingException::$max_text_length = $orig; // restore

        $this->assertStringContainsString('AAA', $debugValue);
        $this->assertStringContainsString('...', $debugValue);
        $this->assertLessThanOrEqual($maxDebugValueLength, $debugValueLength);
    }
}

/**
 * Fixtures.
 */
final class StringableClass
{
    public function __toString(): string
    {
        return 'stringified!';
    }
}

final class JsonLike implements \JsonSerializable
{
    public function __construct(public mixed $value)
    {
    }

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
