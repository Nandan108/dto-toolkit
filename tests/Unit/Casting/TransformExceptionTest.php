<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
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
    /** @psalm-suppress PropertyNotSetInConstructor */
    private BaseDto $dto;

    #[\Override]
    protected function setUp(): void
    {
        $this->dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($this->dto, $this->dto->getErrorList(), $this->dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
    }

    #[\Override]
    protected function tearDown(): void
    {
        ProcessingContext::popFrame();
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testExpectedBuildsCorrectTemplateAndParameters(): void
    {
        $ex = TransformException::expected(
            operand: 'hello',
            expected: 'numeric',
        );

        $this->assertInstanceOf(TransformException::class, $ex);

        // Template
        $this->assertSame('processing.transform.expected', $ex->getMessageTemplate());

        // Parameters
        $params = $ex->getMessageParameters();
        $debug = $ex->getDebugInfo();
        $this->assertSame(['numeric'], $params['expected']);
        $this->assertSame('hello', $debug['orig_value']);
        $this->assertSame(json_encode('hello'), $debug['value']);
        $this->assertSame('string', $params['type']);
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testReasonBuildsCorrectTemplateAndParameters(): void
    {
        $ex = TransformException::reason(
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

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testOperandDebugForObjects(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';

        $ex = TransformException::expected(
            operand: $obj,
            expected: 'string',
        );

        // Debug
        $debug = $ex->getDebugInfo();
        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('foo', $debug['value']);

        // test with an anonymous class that doesn't have a __toString method
        $anon = new class { };
        $ex = TransformException::expected(
            operand: $anon,
            expected: 'string',
        );
        $debug = $ex->getDebugInfo();
        $this->assertSame('anonymous object', $ex->getMessageParameters()['type']);
        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('x', $debug['value']);
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testOperandDebugForJsonSerializable(): void
    {
        $jsonObj = new JsonLike(['x' => 'y']);

        $ex = TransformException::expected(
            operand: $jsonObj,
            expected: 'numeric',
        );

        $debug = $ex->getDebugInfo();

        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('"x":"y"', $debug['value']);
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testOperandDebugForStringableObject(): void
    {
        $stringable = new StringableClass();

        $ex = TransformException::expected(
            operand: $stringable,
            expected: 'numeric',
        );

        $debug = $ex->getDebugInfo();

        $this->assertIsString($debug['value']);
        $this->assertStringContainsString('stringified!', $debug['value']);
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testOperandDebugForResource(): void
    {
        $resource = fopen('php://temp', 'r');

        $ex = TransformException::expected(
            operand: $resource,
            expected: 'string',
        );

        $resource && fclose($resource);

        $params = $ex->getMessageParameters();
        $this->assertSame('resource (stream)', $params['type']);
        $debug = $ex->getDebugInfo();
        $this->assertSame('[unrepresentable]', $debug['value']);
    }

    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function testDebugTruncation(): void
    {
        $long = str_repeat('A', 200);

        // temporarily reduce debug max length
        $orig = ProcessingException::$max_text_length;
        ProcessingException::$max_text_length = 30;

        $ex = TransformException::expected(
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
    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function __toString(): string
    {
        return 'stringified!';
    }
}

final class JsonLike implements \JsonSerializable
{
    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function __construct(public mixed $value)
    {
    }

    #[\Override]
    /** @psalm-suppress MixedAssignment, MixedArrayAccess */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
