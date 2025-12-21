<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use PHPUnit\Framework\TestCase;

final class ProcessingExceptionTest extends TestCase
{
    public function testReturnsCustomErrorCode(): void
    {
        $exception = ProcessingException::failed('custom.reason', errorCode: 'custom-code');

        $this->assertSame('custom-code', $exception->getErrorCode());
    }
}
