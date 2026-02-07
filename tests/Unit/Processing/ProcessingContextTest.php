<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use Nandan108\DtoToolkit\Contracts\ContextStorageInterface;
use Nandan108\DtoToolkit\Contracts\GlobalFrameAwareContextStorageInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\DefaultContextStorage;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Context\ContextException;
use PHPUnit\Framework\TestCase;

final class ProcessingContextTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        ProcessingContext::setStorage(new DefaultContextStorage());
        ProcessingContext::setIncludeProcessingTraceInErrors(null);
    }

    public function testWrapProcessingRejectsErrorModeOverrideOnSameFrame(): void
    {
        $dto = new class extends BaseDto {
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Cannot override errorMode');

        ProcessingContext::wrapProcessing(
            $dto,
            errorMode: ErrorMode::FailFast,
            callback: function () use ($dto): void {
                ProcessingContext::wrapProcessing(
                    $dto,
                    errorMode: ErrorMode::CollectNone,
                    callback: static function (): void {
                    },
                );
            },
        );
    }

    public function testWrapProcessingRejectsErrorListOverrideOnSameFrame(): void
    {
        $dto = new class extends BaseDto {
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Cannot override errorList');

        ProcessingContext::wrapProcessing(
            $dto,
            errorMode: null,
            callback: function () use ($dto): void {
                $dto->setErrorList(new ProcessingErrorList());
                ProcessingContext::wrapProcessing(
                    $dto,
                    errorMode: null,
                    callback: static function (): void {
                    },
                );
            },
        );
    }

    public function testSetDevModeAndIncludeProcessingTraceInErrors(): void
    {
        ProcessingContext::setIncludeProcessingTraceInErrors(null);

        // By default, includeProcessingTraceInErrors should be the same as isDevMode (which is true in this test environment)
        $this->assertTrue(ProcessingContext::isDevMode());
        $this->assertTrue(ProcessingContext::includeProcessingTraceInErrors());

        // Setting dev mode to false should also set includeProcessingTraceInErrors to false by default
        ProcessingContext::setDevMode(false);
        $this->assertFalse(ProcessingContext::isDevMode());
        $this->assertFalse(ProcessingContext::includeProcessingTraceInErrors());

        // Setting includeProcessingTraceInErrors explicitly should override the default behavior
        ProcessingContext::setIncludeProcessingTraceInErrors(true);
        $this->assertTrue(ProcessingContext::includeProcessingTraceInErrors());

        // Setting dev mode back to true should not change includeProcessingTraceInErrors since it was explicitly set
        ProcessingContext::setDevMode(true);
        $this->assertTrue(ProcessingContext::isDevMode());
        $this->assertTrue(ProcessingContext::includeProcessingTraceInErrors());
    }

    public function testSetIncludeProcessingTraceInErrorsRejectsChangeDuringActiveProcessing(): void
    {
        $dto = new class extends BaseDto {
        };

        // Ensure the nested call attempts an actual change regardless of prior test state.
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('setIncludeProcessingTraceInErrors');

        ProcessingContext::wrapProcessing(
            $dto,
            callback: static function (): void {
                ProcessingContext::setIncludeProcessingTraceInErrors(true);
            },
        );
    }

    public function testSetDevModeRejectsChangeDuringActiveProcessing(): void
    {
        $dto = new class extends BaseDto {
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('setDevMode');

        ProcessingContext::wrapProcessing(
            $dto,
            callback: static function (): void {
                ProcessingContext::setDevMode(false);
            },
        );
    }

    public function testSettersUseGlobalProcessingCheckWhenStorageSupportsIt(): void
    {
        // Ensure the setter below attempts a state transition.
        ProcessingContext::setIncludeProcessingTraceInErrors(false);

        $storage = new class implements ContextStorageInterface, GlobalFrameAwareContextStorageInterface {
            #[\Override]
            public function pushFrame(ProcessingFrame $frame): void
            {
                // just a test - not implemented
            }

            #[\Override]
            public function popFrame(): void
            {
                // just a test - not implemented
            }

            #[\Override]
            public function hasFrames(): bool
            {
                return false;
            }

            #[\Override]
            public function currentFrame(): ProcessingFrame
            {
                throw new ContextException('no frame');
            }

            #[\Override]
            public function frames(): array
            {
                return [];
            }

            #[\Override]
            public function hasFramesGlobally(): bool
            {
                return true;
            }
        };

        ProcessingContext::setStorage($storage);
        try {
            $this->expectException(InvalidConfigException::class);
            $this->expectExceptionMessage('processing is active');
            ProcessingContext::setIncludeProcessingTraceInErrors(true);
        } finally {
            ProcessingContext::setStorage(new DefaultContextStorage());
        }
    }
}
