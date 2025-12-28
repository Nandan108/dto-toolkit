<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
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
        // this line only here to have full coverage on caster constructor
        // otherwise, the parent::__construct([$enumClass]); line is not covered for buggy reasons.
        new CastTo\Enum(Status::class);

        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Enum caster: \'Invalid\' is not a valid enum.');
        /** @psalm-suppress ArgumentTypeCoercion, UndefinedClass */
        new CastTo\Enum('Invalid');
    }

    public function testInstantiationWithInvalidEnumClass(): void
    {
        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Enum caster: \''.NotBacked::class.'\' is not a backed enum.');
        /** @psalm-suppress InvalidArgument */
        new CastTo\Enum(NotBacked::class);
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
