<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException as ConfigInvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnumCastTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    #[DataProvider('enumCasesProvider')]
    public function testCastersDoTheirJob(mixed $method, mixed $input, mixed $expected, ?string $exceptionMessage = null): void
    {
        $this->casterTest($method, $input, $expected, [], $exceptionMessage);
    }

    public static function enumCasesProvider(): array
    {
        $circular = ['bar' => 'bar'];
        $circular['foo'] = &$circular; // circular ref

        return [
            // Valid string-backed enum
            // Valid string-backed enum
            'Enum(Status):[] (array)'         => [new CastTo\Enum(Status::class), [], TransformException::class, 'processing.transform.enum.invalid_type'],
            'Enum(Status):draft'              => [new CastTo\Enum(Status::class), 'draft', Status::Draft],
            'Enum(Status):Published'          => [new CastTo\Enum(Status::class), 'published', Status::Published],
            // Invalid value (non-existent key)
            'Enum(Status):invalid'            => [new CastTo\Enum(Status::class), 'archived', TransformException::class, 'processing.transform.enum.invalid_value'],
            // Nullable enum
            'Enum(Status):null'               => [new CastTo\Enum(Status::class), null, TransformException::class, 'processing.transform.enum.invalid_type'],
            // Integer-backed enum
            'Enum(Code):200'                  => [new CastTo\Enum(Code::class), 200, Code::OK],
            'Enum(Code):404'                  => [new CastTo\Enum(Code::class), 404, Code::NotFound],
            // Invalid integer
            'Enum(Code):500'                  => [new CastTo\Enum(Code::class), 500, TransformException::class, 'processing.transform.enum.invalid_value'],
            'Enum(Status):circular-ref'       => [new CastTo\Enum(Status::class), $circular, TransformException::class, 'processing.transform.enum.invalid_type'],
        ];
    }

    public function testInstantiationWithInvalidEnum(): void
    {
        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage("Enum caster: 'NonExistentEnum' is not a valid enum.");
        /** @psalm-suppress UndefinedClass, ArgumentTypeCoercion */
        new CastTo\Enum('NonExistentEnum');
    }

    public function testInstantiationWithInvalidEnumClass(): void
    {
        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Enum caster: \''.NotBacked::class.'\' is not a backed enum.');
        /** @psalm-suppress InvalidArgument */
        new CastTo\Enum(NotBacked::class);
    }

    public function testInvalidEnumErrorMessageInProductionModeDoesNotLeakNamespace(): void
    {
        $originalDevMode = ProcessingContext::isDevMode();
        $dto = new class extends FullDto {
            #[CastTo\Enum(Status::class)]
            public ?string $status = null;
        };
        try {
            foreach ([
                ['devMode' => false, 'expectedClassName' => 'Status'],
                ['devMode' => true, 'expectedClassName' => Status::class],
            ] as $case) {
                ProcessingContext::setDevMode($case['devMode']);
                try {
                    $dto->loadArray(['status' => 'invalid']);
                } catch (TransformException $e) {
                    $this->assertSame('processing.transform.enum.invalid_value', $e->getMessageTemplate());
                    $this->assertSame($case['expectedClassName'], $e->getMessageParameters()['enum']);
                }
            }
        } finally {
            ProcessingContext::setDevMode($originalDevMode);
        }
    }
}

enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
}

enum Code: int
{
    case OK = 200;
    case NotFound = 404;
}

enum NotBacked
{
    case FOO;
    case BAR;
}
