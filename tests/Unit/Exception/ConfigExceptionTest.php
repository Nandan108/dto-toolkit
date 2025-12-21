<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Exception;

use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\MissingDependencyException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use PHPUnit\Framework\TestCase;

final class ConfigExceptionTest extends TestCase
{
    public function testInvalidArgumentDebugInfoIsExposed(): void
    {
        $exception = new InvalidArgumentException('message', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $exception->getDebugInfo());
    }

    public function testInvalidConfigDebugInfoIsExposed(): void
    {
        $exception = new InvalidConfigException('message', ['baz' => 'qux']);

        $this->assertSame(['baz' => 'qux'], $exception->getDebugInfo());
    }

    public function testNodeProducerResolutionExceptionIncludesReason(): void
    {
        $exception = NodeProducerResolutionException::for('demo', 'missing binding');

        $this->assertStringContainsString('Reason: missing binding.', $exception->getMessage());
    }

    public function testMissingDependencyExceptionBuildsMessage(): void
    {
        $exception = new MissingDependencyException('intl', \stdClass::class);

        $this->assertSame(
            "The PHP extension 'intl' is required to use the caster class 'stdClass'.",
            $exception->getMessage(),
        );
    }
}
