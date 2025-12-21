<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use Nandan108\DtoToolkit\Core\ProcessingErrorList;
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

        $first = new ProcessingException('first');
        $second = ProcessingException::failed('second');
        $all = [$first, $second];

        $errors->add($first);
        $errors->add($second);

        self::assertFalse($errors->isEmpty());
        self::assertCount(2, $errors);

        /** @var list<ProcessingException> */
        $errors_all = $errors->all();
        self::assertSame($all, $errors_all);
    }

    public function testIteratorYieldsAddedErrors(): void
    {
        $first = new ProcessingException('first');
        $second = new ProcessingException('second');

        $errors = new ProcessingErrorList();
        $errors->add($first);
        $errors->add($second);

        self::assertSame([$first, $second], iterator_to_array($errors));
    }
}
