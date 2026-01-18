<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use PHPUnit\Framework\TestCase;

final class ValidateBaseTest extends TestCase
{
    public function testFailHelperThrowsGuardException(): void
    {
        $validator = new class extends ValidatorBase {
            #[\Override]
            public function validate(mixed $value, array $args = []): void
            {
                $this->fail('dummy_failure');
            }
        };

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $this->expectException(\Nandan108\DtoToolkit\Exception\Process\GuardException::class);
            $this->expectExceptionMessage('processing.guard.dummy_failure');
            $validator->validate('anything');
        } finally {
            ProcessingContext::popFrame();
        }
    }
}
