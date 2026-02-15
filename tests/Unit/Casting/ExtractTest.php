<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use Nandan108\PropAccess\PropAccess;
use Nandan108\PropPath\Exception\EvaluationErrorCode;
use Nandan108\PropPath\Support\EvaluationFailureDetails;
use PHPUnit\Framework\TestCase;

final class ExtractTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        // Ensure that the test environment is clean
        PropAccess::bootDefaultResolvers();
    }

    public function testExtract(): void
    {
        $arrayAccess = new \ArrayIterator(['A', 'B', 'C']);

        $dto = ExtractTestFixture::newWithContext([
            'foo' => 'bar',
            'baz' => 'qux',
        ])->loadArray([
            'propVal' => ['A', 'B', $arrayAccess],
        ]);
        $dto->setGetterVal(['D', 'E', 'F']);

        ProcessingContext::wrapProcessing($dto, function () use ($dto) {
            // Extract through objects, via props or getters
            $this->casterTest(new CastTo\Extract('foo[propVal.1, context.baz]'), ['foo' => $dto], ['B', 'qux']);
            $this->casterTest(new CastTo\Extract('dto.getterVal.1'), ['dto' => $dto], 'E');
            $this->casterTest(new CastTo\Extract('propVal.2.2'), $dto, 'C');
            $this->casterTest(new CastTo\Extract('propVal.2.2'), $dto, 'C');

            // Extract from ArrayAccess with invalid path
            $this->casterTest(new CastTo\Extract('propVal.2.!3'), $dto, ExtractionException::class);

            // Extract with invalid path input
            $this->casterTest(new CastTo\Extract('foo.bar.!3'), ['foo' => ['bar' => ['a', 'b', 'c']]], ExtractionException::class);
        });
    }

    public function testExtractFailsWithInvalidPath(): void
    {
        // Extract with invalid path input
        try {
            new CastTo\Extract('foo.-bar!');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError::class, $e);
            $this->assertStringContainsString('Invalid path provided', $e->getMessage());
        }
    }

    public function testExtractFailureExposesTypedFailureDetails(): void
    {
        $dto = ExtractTestFixture::new()->loadArray([
            'propVal' => ['A', 'B', ['C']],
        ]);

        ProcessingContext::wrapProcessing($dto, function () use ($dto): void {
            try {
                (new CastTo\Extract('propVal[2].!3.0'))->getProcessingNode($dto)($dto);
                $this->fail('Expected ExtractionException was not thrown.');
            } catch (ExtractionException $e) {
                $this->assertInstanceOf(EvaluationFailureDetails::class, $e->failure);
                $this->assertSame(EvaluationErrorCode::KEY_NOT_FOUND_ARRAY, $e->failure->code);
                $this->assertSame('$value.propVal.[2].3', $e->failure->getPropertyPath());
            }
        });
    }
}

/**
 * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedProperty
 */
final class ExtractTestFixture extends FullDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public mixed $propVal = null;
    private mixed $getterVal = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getGetterVal(): mixed
    {
        return $this->getterVal;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function setGetterVal(mixed $value): void
    {
        $this->getterVal = $value;
    }
}
