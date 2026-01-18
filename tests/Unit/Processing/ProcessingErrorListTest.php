<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use PHPUnit\Framework\TestCase;

final class ProcessingErrorListTest extends TestCase
{
    public function testAddAndCountAndAll(): void
    {
        $errors = new ProcessingErrorList();

        self::assertTrue($errors->isEmpty());
        self::assertSame([], $errors->all());
        self::assertCount(0, $errors);

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $first = new ProcessingException('first');
            $second = ProcessingException::failed('second');
        } finally {
            ProcessingContext::popFrame();
        }
        $all = [$first, $second];

        $errors->add($first);
        $errors->add($second);

        self::assertFalse($errors->isEmpty());
        self::assertCount(2, $errors);

        $errors_all = $errors->all();
        self::assertSame($all, $errors_all);
    }

    public function testIteratorYieldsAddedErrors(): void
    {
        $dto = new class extends BaseDto {
        };
        $errorMode = $dto->getErrorMode();
        $errorList = $dto->getErrorList();
        $frame = new ProcessingFrame($dto, $errorList, $errorMode);
        ProcessingContext::pushFrame($frame);
        try {
            $first = new ProcessingException('first');
            $second = new ProcessingException('second');

            // Opportunistic test: Check that the ProcessingContext returns the correct frame info
            self::assertSame($errorMode, ProcessingContext::errorMode());
            self::assertSame($errorList, ProcessingContext::errorList());
        } finally {
            ProcessingContext::popFrame();
        }

        $errors = new ProcessingErrorList();
        $errors->add($first);
        $errors->add($second);

        self::assertSame([$first, $second], iterator_to_array($errors));
    }
}
