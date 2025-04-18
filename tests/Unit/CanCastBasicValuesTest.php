<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use DateTimeImmutable;
use Mockery;
// use Nandan108\DtoToolkit\Core\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;


final class CanCastBasicValuesTest extends TestCase
{
    #[DataProvider('builtinCastProvider')]
    public function test_builtin_cast_methods(mixed $method, mixed $input, mixed $expected, array $args = [], ?string $exceptionMessage = null): void
    {
        // /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;
        };

        // create caster Attribute using static helper method, and from it get the caster Closure
        if (is_string($method)) {
            $casterAttribute = new CastTo($method, args: $args);
            $caster          = $casterAttribute->getCaster($dto);
        } elseif ($method instanceof CastTo) {
            $caster = $method->getCaster($dto);
        } else {
            $this->fail('Invalid method type: ' . gettype($method));
        }

        // Call the caster closure with the input value and get the result
        try {
            $result = $caster($input);

            if (is_object($expected)) {
                $this->assertInstanceOf(get_class($expected), $result);
                $this->assertEquals($expected, $result); // compares datetime value
            } else {
                $this->assertSame($expected, $result);
            }
        } catch (\Exception $e) {
            if (is_string($expected) && class_exists($expected) && is_a($e, $expected)) {
                $this->assertInstanceOf($expected, $e);
                if ($exceptionMessage !== null && $exceptionMessage > '') {
                    $this->assertStringContainsString($exceptionMessage, $e->getMessage());
                }
            } else {
                throw $e;
            }
        }

    }

    public static function builtinCastProvider(): array
    {
        $dateTime    = date('Y-m-d H:i:s');
        $dateTimeObj = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        return [
            'Boolean:false'                     => [new CastTo\Boolean, false, false],
            'Boolean:1'                         => [new CastTo\Boolean, '1', true],
            'Boolean:yes'                       => [new CastTo\Boolean, 'yes', true],
            'Boolean:yesss'                     => [new CastTo\Boolean, 'yessss', null],
            'Boolean(nullable:false):[],'       => [new CastTo\Boolean(nullable: false), [], false],
            'Boolean(nullable:false):[1]'       => [new CastTo\Boolean(nullable: false), [1], true],
            'Boolean(nullable:false,strict):[]' => [new CastTo\Boolean(nullable: false, strict: true), [], CastingException::class],
            'Str:42'                            => [new CastTo\Str, 42, '42'],
            'Str:""'                            => [new CastTo\Str, '', ''],
            'Str(nullable):""'                  => [new CastTo\Str(nullable: true), '', null],
            'Trimmed'                           => [new CastTo\Trimmed, '  hello ', 'hello'],
            'Trimmed:left'                      => [new CastTo\Trimmed('to', 'left'), 'othello', 'hello'],
            'Trimmed:right'                     => [new CastTo\Trimmed('to', 'right'), 'hotelot', 'hotel'],
            'Slug'                              => [new CastTo\Slug(separator: '.'), 'Let\'s go for Smörgåsbord', 'let.s.go.for.smorgasbord'],
            'Capitalized'                       => [new CastTo\Capitalized, 'hello', 'Hello'],
            'Uppercase'                         => [new CastTo\Uppercase, 'hello', 'HELLO'],
            'DateTime'                          => [new CastTo\DateTime(format: 'Y-m-d H:i:s'), $dateTime, $dateTimeObj],
            'DateTime:invalid date'             => [new CastTo\DateTime(format: 'Y-m-d H:i:s'), 'invalid date', null],
            'DateTime:\stdClass'                => [new CastTo\DateTime(format: 'Y-m-d H:i:s'), new \stdClass(), null],
            'ArrayFromCsv'                      => [new CastTo\ArrayFromCsv, 'a,b,c', ['a', 'b', 'c']],
            'ArrayFromCsv:sep:"-"'              => [new CastTo\ArrayFromCsv(separator: '-'), 'a-b-c', ['a', 'b', 'c']],
            'ArrayFromCsv:empty'                => [new CastTo\ArrayFromCsv, '', ['']],
            'Ceil'                              => [new CastTo\Ceil, 1.2, 2],
            'CsvFromArray'                      => [new CastTo\CsvFromArray, ['a', 'b', 'c'], 'a,b,c'],
            'CsvFromArray:separator:"-"'        => [new CastTo\CsvFromArray(separator: '-'), ['a', 'b', 'c'], 'a-b-c'],
            'Floating'                          => [new CastTo\Floating, '3.14', 3.14],
            'Floor:1.2'                         => [new CastTo\Floor, 1.2, 1],
            'Floor'                             => [new CastTo\Floor, null, 0],
            'Floor(nullable):not-a-number'      => [new CastTo\Floor(nullable: true), 'not-a-number', null],
            'Integer'                           => [new CastTo\Integer, '123.532', 123],
            'Lowercase'                         => [new CastTo\Lowercase, 'HELLo!', 'hello!'],
            'Rounded(2)'                        => [new CastTo\Rounded(2), 0.991, 0.99],
            'Rounded(1)'                        => [new CastTo\Rounded(1), 0.991, 1.0],
            // Valid string-backed enum
            'Enum(Status):draft'                => [new CastTo\Enum(Status::class), 'draft', Status::Draft],
            'Enum(Status):Published'            => [new CastTo\Enum(Status::class), 'published', Status::Published],
            // Invalid value (non-existent key)
            'Enum(Status):invalid'              => [new CastTo\Enum(Status::class), 'archived', CastingException::class, [], 'Value \'archived\' is invalid for this enum'],
            // Nullable enum (valid null)
            'Enum(Status):null'                 => [new CastTo\Enum(Status::class, nullable: true), null, null],
            // Nullable enum (invalid null)
            'Enum(Status):null-blocked'         => [new CastTo\Enum(Status::class, nullable: false), null, CastingException::class, [], 'Enum caster received null, but nullable = false.'],
            // Integer-backed enum
            'Enum(Code):200'                    => [new CastTo\Enum(Code::class), 200, Code::OK],
            'Enum(Code):404'                    => [new CastTo\Enum(Code::class), 404, Code::NotFound],
            // Invalid integer
            'Enum(Code):500'                    => [new CastTo\Enum(Code::class), 500, CastingException::class, [], 'Value \'500\' is invalid for this enum'],
            // Invalid Enum Class
            'Enum(Invalid):500'                 => [new CastTo\Enum('Invalid'), 'any-val', CastingException::class, [], 'Enum caster: \'Invalid\' is not a valid enum.'],
            // Invalid Enum Class
            'Enum(NotBacked):500'               => [new CastTo\Enum(NotBacked::class), 'any-val', CastingException::class, [], 'Enum caster: \''.NotBacked::class.'\' is not a backed enum.'],
        ];
    }
}

enum Status: string
{
    case Draft     = 'draft';
    case Published = 'published';
}

enum Code: int
{
    case OK       = 200;
    case NotFound = 404;
}

enum NotBacked
{
    case FOO;
    case BAR;
}