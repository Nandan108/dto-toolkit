<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use PHPUnit\Framework\TestCase;

final class ValidateBaseTest extends TestCase
{
    public function testFailHelperThrowsGuardException(): void
    {
        $validator = new class extends ValidatorBase {
            #[\Override]
            public function validate(mixed $value, array $args = []): void
            {
                throw GuardException::failed('dummy_failure');
            }
        };

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $this->expectException(GuardException::class);
            $this->expectExceptionMessage('processing.guard.dummy_failure');
            $validator->validate('anything');
        } finally {
            ProcessingContext::popFrame();
        }
    }
}
