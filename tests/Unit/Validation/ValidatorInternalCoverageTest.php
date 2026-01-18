<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Assert\CompareTo;
use Nandan108\DtoToolkit\Assert\CompareToExtract;
use Nandan108\DtoToolkit\Assert\IsBlank;
use Nandan108\DtoToolkit\Assert\IsType;
use Nandan108\DtoToolkit\Assert\Support\SequenceMatcher;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\PropPath\PropPath;
use PHPUnit\Framework\TestCase;

enum LocalBackedEnum: string
{
    case A = 'a';
}

enum LocalUnitEnum
{
    case A;
}

final class ValidatorInternalCoverageTest extends TestCase
{
    public function testCompareToPrivateHelpers(): void
    {
        $this->callPrivate(CompareTo::class, 'assertOperator', ['==']);

        try {
            $this->callPrivate(CompareTo::class, 'assertOperator', ['??']);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('invalid operator', $e->getMessage());
        }

        $this->assertSame(
            'a',
            $this->callPrivate(CompareTo::class, 'normalizeOperand', [LocalBackedEnum::A, null, true]),
        );
        $this->assertSame(
            LocalUnitEnum::A,
            $this->callPrivate(CompareTo::class, 'normalizeOperand', [LocalUnitEnum::A, null, true]),
        );

        $timestamp = $this->callPrivate(
            CompareTo::class,
            'normalizeOperand',
            [new \DateTimeImmutable('2020-01-01'), null, true],
        );
        $this->assertIsInt($timestamp);
    }

    public function testCompareToDirectValidate(): void
    {
        $validator = new CompareTo('==', 10);
        $validator->validate(10, ['==', 10]);
        $this->assertTrue(true);
    }

    public function testCompareToExtractDirectValidate(): void
    {
        PropPath::boot();

        $dto = new class extends FullDto {
        };
        $dto->withContext(['expected' => 'ok']);
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);

        try {
            $validator = new CompareToExtract('==', '$context.expected');
            $validator->validate('ok');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertTrue(true);
    }

    public function testSequenceMatcherPrivateHelpers(): void
    {
        $matcher = new class {
            use SequenceMatcher;

            public function call(string $method, array $args = []): mixed
            {
                $ref = new \ReflectionMethod($this, $method);
                /** @psalm-suppress UnusedMethodCall */
                $ref->setAccessible(true);

                return $ref->invokeArgs($this, $args);
            }
        };

        $matcher->call('assertValidPosition', [null]);
        $matcher->call('assertValidPosition', ['start']);

        try {
            $matcher->call('assertValidPosition', ['middle']);
            $this->fail('Expected InvalidConfigException was not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('invalid', $e->getMessage());
        }

        $this->assertTrue($matcher->call('isRewindableIterable', [[1, 2]]));
        $this->assertFalse($matcher->call('isRewindableIterable', [$this->nonRewindableIterator()]));
        $this->assertTrue($matcher->call('isRewindableIterable', [new \ArrayObject([1])]));
        $this->assertFalse($matcher->call('isRewindableIterable', [new \stdClass()]));

        $this->assertSame([1, 2], $matcher->call('iterableToList', [new \ArrayIterator([1, 2])]));

        $this->assertTrue($matcher->call('containsString', ['foobar', 'foo', 'start']));
        $this->assertTrue($matcher->call('containsSequence', [[1, 2, 3], [2, 3], null]));
        $this->assertFalse($matcher->call('containsSequence', [[1, 2], [1, 2, 3], null]));
        $this->assertFalse($matcher->call('containsSequenceAnywhere', [[1, 2, 3], [3, 4]]));
    }

    public function testIsBlankPrivateHelper(): void
    {
        $validator = new IsBlank(true);
        $this->assertTrue($this->callPrivate($validator, 'isBlankValue', [[]]));
        $this->assertFalse($this->callPrivate($validator, 'isBlankValue', [0]));
        $this->assertTrue($this->callPrivate($validator, 'isBlankValue', [$this->emptyIterator()]));
    }

    public function testIsTypePrivateHelpers(): void
    {
        $validator = new IsType('int');
        $this->assertTrue($this->callPrivate($validator, 'matchesType', [1, 'int']));
    }

    private function nonRewindableIterator(): \Generator
    {
        yield 1;
    }

    private function emptyIterator(): \Generator
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (false) {
            yield 1;
        }
    }

    /**
     * Private helper that calls a private or protected method on a given object or class.
     *
     * @param object|class-string $target
     */
    private function callPrivate(object | string $target, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($target, $method);
        /** @psalm-suppress UnusedMethodCall */
        $ref->setAccessible(true);

        return $ref->invokeArgs(\is_string($target) ? null : $target, $args);
    }
}
