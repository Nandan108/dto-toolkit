<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\Core\BaseDto;
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
}
