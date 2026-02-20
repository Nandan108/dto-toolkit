<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\PropPath\PropPath;
use PHPUnit\Framework\TestCase;

final class CompareToExtractTest extends TestCase
{
    // boot PropPath on setup
    #[\Override()]
    public function setUp(): void
    {
        parent::setUp();
        PropPath::boot();
    }

    public function testCompareToExtractUsesRightPath(): void
    {
        $dto = new class extends FullDto {
            #[Assert\CompareToExtract('==', '$dto.right')]
            public int $left = 0;

            public int $right = 5;
        };

        $dto->fill(['left' => 5])->processInbound();
        $this->assertSame(5, $dto->left);

        // $dto->fill(['left' => 5, 'right' => 6]);

        // $this->expectException(GuardException::class);
        // $this->expectExceptionMessage('processing.guard.compare_to');
        // $dto->processInbound();
    }

    public function testCompareToExtractUsesLeftPath(): void
    {
        $complexProperty = ['some' => ['path' => 'ok']];

        $dto = new class extends FullDto {
            #[Assert\CompareToExtract(leftPath: 'some.path', op: '==', rightPath: '$context.expected')]
            public ?array $check = null;

        };

        $dto->withContext(['expected' => 'ok']);
        $dto->fill(['check' => $complexProperty]);
        $dto->processInbound();
        $this->assertSame($complexProperty, $dto->check);

        $dto->withContext(['expected' => 'nope']);
        try {
            $dto->processInbound();
            $this->fail('Expected GuardException was not thrown.');
        } catch (GuardException $e) {
            $this->assertSame("check{Assert\CompareToExtract}: '==' comparison failed after extraction.", $e->getMessage());
        }
    }

    public function testCompareToExtractRejectsInvalidPath(): void
    {
        $this->expectException(ExtractionSyntaxError::class);
        $this->expectExceptionMessage('CompareToExtract: Invalid path provided');

        new Assert\CompareToExtract('==', 'foo.-bar!');
    }

    public function testCompareToExtractRejectsInvalidLeftPath(): void
    {
        $this->expectException(ExtractionSyntaxError::class);
        $this->expectExceptionMessage('CompareToExtract: Invalid path provided');

        new Assert\CompareToExtract('==', '$dto.value', 'foo.-bar!');
    }

    public function testCompareToExtractBubblesExtractionFailure(): void
    {
        $dto = new class extends FullDto {
            #[Assert\CompareToExtract('==', '$dto.right.!1')]
            public int $left = 0;

            public array $right = [];
        };

        $dto->fill(['left' => 1]);

        $this->expectException(ExtractionException::class);
        $dto->processInbound();
    }
}
