<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class CastingExceptionTest extends TestCase
{
    public function testCastingFailureWithObjectOperand(): void
    {
        $exception = CastingException::castingFailure(
            className: $className = '\App\CastTo\Slug',
            operand: new \stdClass(),
            args: $args = ['test']
        );
        $serializedArgs = json_encode($args);

        $this->assertInstanceOf(CastingException::class, $exception);
        $this->assertStringContainsString("Class $className($serializedArgs) failed to cast", $exception->getMessage());
        $this->assertEquals($className, $exception->className);
        $this->assertIsObject($exception->operand);
    }

    public function testCastingFailureWithScalarOperand(): void
    {
        $exception = CastingException::castingFailure(
            className: $className = 'App\CastTo\IntType',
            operand: 'hello world',
            args: [],
        );

        $this->assertStringContainsString('hello world', $exception->getMessage());
        $this->assertEquals($className, $exception->className);
    }

    public function testCastingFailureWithUnrepresentableValue(): void
    {
        $resource = fopen('php://temp', 'r');
        $exception = CastingException::castingFailure(
            className: $className = 'App\CastTo\ResourceTest',
            operand: $resource,
            args: [],
        );
        if ($resource) {
            fclose($resource);
        }

        $this->assertStringContainsString('failed to cast resource', $exception->getMessage());
    }

    public function testCastingFailureWithArrayOperand(): void
    {
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\ToArray',
            operand: ['a' => 1, 'b' => 2],
            args: [],
        );

        $this->assertStringContainsString('array', $exception->getMessage());
        $this->assertStringContainsString('a', $exception->getMessage());
    }

    public function testCastingFailureWithMessageOverride(): void
    {
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\Custom',
            operand: 'X',
            args: [],
            messageOverride: 'Something went wrong',
        );

        $this->assertStringStartsWith('Something went wrong:', $exception->getMessage());
    }

    public function testCastingFailureWithUnencodableArgs(): void
    {
        $resource = fopen('php://temp', 'r');
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\BrokenArgs',
            operand: 'foo',
            args: ['bad' => $resource],
        );
        if ($resource) {
            fclose($resource);
        }

        $this->assertStringContainsString('(?args?)', $exception->getMessage());
    }

    public function testCastingFailureWithJsonEncodingFailure(): void
    {
        $operand = new JsonLike();
        // set up a cyclic reference
        // this will cause json_encode to fail
        $operand->prop = (object) ['x' => 'y'];
        $operand->prop->x = $operand;

        $exception = CastingException::castingFailure(
            className: 'App\CastTo\BadJson',
            operand: $operand,
            args: [],
        );

        $this->assertStringContainsString('not serializable', $exception->getMessage());
    }

    public function testCastingFailureMessageTruncatesOperand(): void
    {
        CastingException::$maxOperandTextLength = 20;

        $longString = str_repeat('A', 100);
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\Truncator',
            operand: $longString,
            args: [],
        );

        $this->assertStringContainsString(substr($longString, 0, 15), $exception->getMessage());
        $this->assertStringContainsString('...', $exception->getMessage());
    }

    public function testCastingFailureWithToStringObject(): void
    {
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\ToStringCaster',
            operand: new StringableClass(),
            args: [],
        );

        $this->assertStringContainsString('stringified!', $exception->getMessage());
    }

    public function testCastingFailureWithJsonSerializableObject(): void
    {
        $exception = CastingException::castingFailure(
            className: 'App\CastTo\JsonCaster',
            operand: new JsonLike(),
            args: [],
        );

        $this->assertStringContainsString('"x":"y"', $exception->getMessage());
    }
}

final class StringableClass
{
    public function __toString(): string
    {
        return 'stringified!';
    }
}

final class JsonLike implements \JsonSerializable
{
    public function __construct(public mixed $prop = 'foo')
    {
        $this->prop = ['x' => 'y'];
    }

    #[\Override]
    public function jsonSerialize(): mixed
    {
        return ['prop' => $this->prop];
    }
}
