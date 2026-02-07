<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use PHPUnit\Framework\TestCase;

final class ProcessingExceptionTest extends TestCase
{
    public function testReturnsCustomErrorCode(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason', errorCode: 'custom-code');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertSame('custom-code', $exception->getErrorCode());
    }

    public function testThrowerNodeNameDefaultsToNull(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $exception = ProcessingException::failed('custom.reason');
        } finally {
            ProcessingContext::popFrame();
        }

        $this->assertNull($exception->getThrowerNodeName());
    }

    public function testThrowerNodeNameIsAutoEnrichedByProcessingNodeMetaInProdTraceMode(): void
    {
        BaseDto::clearAllCaches();
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $dto = new class extends FullDto {
            #[CastTo\Boolean]
            public mixed $value = null;
        };

        try {
            $dto->fill(['value' => 'not-bool'])->processInbound();
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('CastTo\Boolean', $e->getThrowerNodeName());
            $this->assertSame('value', $e->getPropertyPath());
        } finally {
            ProcessingContext::setIncludeProcessingTraceInErrors(null);
            BaseDto::clearAllCaches();
        }
    }

    public function testThrowerNodeNameCanBeCustomizedByNodeProducer(): void
    {
        BaseDto::clearAllCaches();
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $dto = new class extends FullDto {
            #[CastTo(NodeNameOverrideCaster::class)]
            public mixed $value = null;
        };

        try {
            $dto->fill(['value' => 'x'])->processInbound();
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('Custom\BooleanLike', $e->getThrowerNodeName());
            $this->assertSame('value', $e->getPropertyPath());
        } finally {
            ProcessingContext::setIncludeProcessingTraceInErrors(null);
            BaseDto::clearAllCaches();
        }
    }
}

final class NodeNameOverrideCaster extends CastBaseNoArgs
{
    /** @var ?truthy-string */
    protected static ?string $nodeName = 'Custom\BooleanLike';

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        throw ProcessingException::failed('custom.reason');
    }
}
