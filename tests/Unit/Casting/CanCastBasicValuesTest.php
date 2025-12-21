<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

// use Nandan108\DtoToolkit\Core\CastTo;
// use Nandan108\DtoToolkit\Traits\CanCastBasicValues;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Enum\IntCastMode;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CanCastBasicValuesTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    #[DataProvider('builtinCastProvider')]
    public function testBuiltinCastMethods(mixed $method, mixed $input, mixed $expected, array $args = [], ?string $exceptionMessage = null): void
    {
        $this->casterTest($method, $input, $expected, $args, $exceptionMessage);
    }

    public static function builtinCastProvider(): array
    {
        $stringable = new class($strVal = 'foo') {
            public function __construct(public string $strVal)
            {
            }

            public function __toString(): string
            {
                return $this->strVal;
            }
        };
        $circular = ['bar' => 'bar'];
        $circular['foo'] = &$circular; // circular ref

        $someString = 'This is a random string: '.base64_encode(random_bytes(10));

        return [
            'Boolean:false'                   => [new CastTo\Boolean(), false, false],
            'Boolean:"1"'                     => [new CastTo\Boolean(), '1', true],
            'Boolean:yes'                     => [new CastTo\Boolean(), 'yes', true],
            'Boolean:yesss (non-bool string)' => [new CastTo\Boolean(), 'yessss', TransformException::class],
            'Boolean:12 (non-zero int)'       => [new CastTo\Boolean(), 12, true],
            'Boolean:"10"'                    => [new CastTo\Boolean(), '10', TransformException::class],
            'Boolean:[] (array)'              => [new CastTo\Boolean(), [], TransformException::class],
            'Boolean:null'                    => [new CastTo\Boolean(), null, TransformException::class],
            'Str:42'                          => [new CastTo\Str(), 42, '42'],
            'Str:""'                          => [new CastTo\Str(), '', ''],
            'Str:"foo"'                       => [new CastTo\Str(), $stringable, 'foo'],
            'Str:null'                        => [new CastTo\Str(), null, TransformException::class],
            'Trimmed'                         => [new CastTo\Trimmed(), '  hello ', 'hello'],
            'Trimmed:left'                    => [new CastTo\Trimmed('to', 'left'), 'othello', 'hello'],
            'Trimmed:right'                   => [new CastTo\Trimmed('to', 'right'), 'hotelot', 'hotel'],
            'Capitalized'                     => [new CastTo\Capitalized(), 'hello', 'Hello'],
            'Uppercase'                       => [new CastTo\Uppercase(), 'hello', 'HELLO'],
            'Split'                           => [new CastTo\Split(), 'a,b,c', ['a', 'b', 'c']],
            'Split:sep:"-"'                   => [new CastTo\Split(separator: '-'), 'a-b-c', ['a', 'b', 'c']],
            'Split:empty'                     => [new CastTo\Split(), '', ['']],
            'Join'                            => [new CastTo\Join(), ['a', 'b', 'c'], 'a,b,c'],
            'Join:separator:"-"'              => [new CastTo\Join(separator: '-'), ['a', 'b', 'c'], 'a-b-c'],
            'Join:not-an-array'               => [new CastTo\Join(separator: '-'), 'not-an-array', TransformException::class],
            'Floating:bool'                   => [new CastTo\Floating(), false, TransformException::class],
            'Floating:numeric-int'            => [new CastTo\Floating(), 3, 3.0],
            'Floating:numeric-float'          => [new CastTo\Floating(), 3.14, 3.14],
            'Floating:numeric-string'         => [new CastTo\Floating(), '3.14', 3.14],
            'Floating:stringable'             => [new CastTo\Floating(), new $stringable('123.4'), 123.4],
            'Integer:not-a-number'            => [new CastTo\Integer(), 'not-a-number', TransformException::class],
            'Integer:numeric-stringable'      => [new CastTo\Integer(), new $stringable('123.4'), 123],
            'Integer:bool'                    => [new CastTo\Integer(), false, 0],
            'Integer:Ceil'                    => [new CastTo\Integer(IntCastMode::Ceil), '123.532', 124],
            'Integer:Ceil_neg'                => [new CastTo\Integer(IntCastMode::Ceil), '-123.532', -123],
            'Integer:Floor'                   => [new CastTo\Integer(IntCastMode::Floor), '123.532', 123],
            'Integer:Floor_neg'               => [new CastTo\Integer(IntCastMode::Floor), '-123.532', -124],
            'Integer:Round'                   => [new CastTo\Integer(IntCastMode::Round), '123.532', 124],
            'Integer:Round_neg'               => [new CastTo\Integer(IntCastMode::Round), '-123.532', -124],
            'Integer:Trunc'                   => [new CastTo\Integer(IntCastMode::Trunc), '123.532', 123],
            'Integer:Trunc_neg'               => [new CastTo\Integer(IntCastMode::Trunc), '-123.532', -123],
            'Lowercase'                       => [new CastTo\Lowercase(), 'HELLo!', 'hello!'],
            'Rounded(2)'                      => [new CastTo\Rounded(2), 0.991, 0.99],
            'Rounded(1)'                      => [new CastTo\Rounded(1), 0.991, 1.0],
            'Rounded(stringable obj)'         => [new CastTo\Rounded(2), new $stringable('0.991'), 0.99],
            'Rounded:not-a-number'            => [new CastTo\Rounded(1), 'not-a-number', TransformException::class],
            'JsonEncode(valid)'               => [new CastTo\Json(), [1, 'a', null, true], '[1,"a",null,true]'],
            'JsonEncode(invalid)'             => [new CastTo\Json(), $circular, TransformException::class, [], 'processing.transform.json.encoding_failed'],
            // ReplaceIf
            'ReplaceIf:string-match'          => [new CastTo\ReplaceWhen('foo', 'bar'), 'foo', 'bar'],
            'ReplaceIf:string-mismatch'       => [new CastTo\ReplaceWhen('foo', 'bar'), 'qux', 'qux'],
            'ReplaceIf:string-strict-match'   => [new CastTo\ReplaceWhen('1', 'bar', strict: true), 1, 1],
            'ReplaceIf:string-lose-match'     => [new CastTo\ReplaceWhen('1', 'bar', strict: false), 1, 'bar'],
            'ReplaceIf:array-match'           => [new CastTo\ReplaceWhen(['a', 'b'], 'R'), 'b', 'R'],
            'ReplaceIf:match-mismatch'        => [new CastTo\ReplaceWhen(['a', 'b'], 'R'), 'q', 'q'],
            'ReplaceIf:array-strict-match'    => [new CastTo\ReplaceWhen(['1', '3'], 'foo', strict: true), 3, 3],
            'ReplaceIf:array-lose-match'      => [new CastTo\ReplaceWhen(['1', '3'], 'foo', strict: false), 3, 'foo'],
            'FromBase64'                      => [new CastTo\FromBase64(), base64_encode($someString), $someString],
            'FromBase64:invalid'              => [new CastTo\FromBase64(), ' invalid string ', TransformException::class],
            'Base64Encode'                    => [new CastTo\Base64(), $someString, base64_encode($someString)],
            'Base64Encode:invalid'            => [new CastTo\Base64(), [], TransformException::class],
        ];
    }

    public function testSomeMoreCasters(): void
    {
        // parent-constructor call line in caster constructor is not covered
        // if constructor is instanciated within the DataProvider method, for some reason. So test them separately.
        $this->casterTest(CastTo\NumericString::class, '1234.456', '1 234,46', ['2', ',', ' ']);
        $this->casterTest(CastTo\NumericString::class, 'not-a-number', TransformException::class, ['2', ',', ' ']);
    }

    public function testDateTime(): void
    {
        $dateTime = date('Y-m-d H:i:s');
        $dateTimeObj = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        $this->casterTest(new CastTo\DateTime(format: DateTimeFormat::SQL), $dateTime, $dateTimeObj);
    }

    public function testDateTimeWithInvalidDateString(): void
    {
        $this->casterTest(
            new CastTo\DateTime(format: DateTimeFormat::SQL),
            'invalid date',
            TransformException::class,
            [],
            'processing.transform.date.parsing_failed',
        );
    }

    public function testDateTimeWithNonStringValue(): void
    {
        $this->casterTest(new CastTo\DateTime(format: DateTimeFormat::SQL), new \stdClass(), TransformException::class);
    }
}
