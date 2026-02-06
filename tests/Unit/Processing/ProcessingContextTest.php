<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use PHPUnit\Framework\TestCase;

final class ProcessingContextTest extends TestCase
{
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
}
