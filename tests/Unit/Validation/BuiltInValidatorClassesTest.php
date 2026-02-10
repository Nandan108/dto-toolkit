<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Assert as V;
use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

enum DummyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

enum DummyUnitStatus
{
    case Alpha;
    case Beta;
}

final class BuiltInValidatorClassesTest extends TestCase
{
    /**
     * @dataProvider validatorProvider
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string<ValidatorBase>|ValidatorBase $validatorClass
     * @param class-string<\Throwable>                  $exceptionClass
     */
    public function testValidatorBehavior(
        string | ValidatorBase $validatorClass,
        array $params,
        mixed $value,
        ?string $exceptionMessage = null,
        string $exceptionClass = GuardException::class,
    ): void {
        // /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
        };

        // create validator Attribute using static helper method, and from it get the caster Closure
        if ($validatorClass instanceof ValidatorBase) {
            $validatorClass->args = $params;
            $validator = $validatorClass->getProcessingNode($dto);
        } elseif (is_subclass_of($validatorClass, ValidatorBase::class, true)) {
            /** @psalm-suppress UnsafeInstantiation */
            try {
                $validatorAttribute = new $validatorClass(...$params);
                $validator = $validatorAttribute->getProcessingNode($dto);
            } catch (\Throwable $e) {
                if (null !== $exceptionMessage && is_a($e, $exceptionClass, true)
                    && str_contains($e->getMessage(), $exceptionMessage)
                ) {
                    self::assertTrue(true); // constructor guard triggered as expected

                    return;
                }
                throw $e;
            }
        } else {
            $this->fail('Invalid method type: '.gettype($validatorClass));
        }

        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        ProcessingContext::pushPropPath('test');
        try {
            if ($validator->instance instanceof BootsOnDtoInterface) {
                $validator->instance->bootOnDto();
            }

            // positive check
            if (null === $exceptionMessage) {
                $validator($value);
                self::assertTrue(true); // reached without exception

                return;
            }

            // negative check
            $this->expectException($exceptionClass);
            $this->expectExceptionMessage($exceptionMessage);
            $validator($value);
        } finally {
            ProcessingContext::popPropPath();
            ProcessingContext::popFrame();
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public static function validatorProvider(): array
    {
        return [
            'date format invalid'                     => [V\DateFormat::class, ['Y-m-d'], '2025/99/99', 'Date format does not match format Y-m-d.'],
            'json type mismatch'                      => [V\Json::class, [['object']], '"str"', 'JSON type mismatch.'],
            'enum backed value invalid instance'      => [V\EnumBackedValue::class, [DummyStatus::class], DummyStatus::Draft, 'Expected backing value of DummyStatus, got an instance.'],
            'enum backed value invalid type'          => [V\EnumBackedValue::class, [DummyStatus::class], new \stdClass(), 'Expected backing value of DummyStatus, got stdClass.'],
            'date format empty'                       => [V\DateFormat::class, [''], '2025-01-02', 'DateFormat validator requires a format string.', InvalidArgumentException::class],

            'date format valid'                       => [V\DateFormat::class, ['Y-m-d'], '2025-01-02'],
            'email invalid'                           => [V\Email::class, [], 'not-an-email', 'Invalid email address.'],
            'email valid'                             => [V\Email::class, [], 'test@example.com'],
            'json invalid'                            => [V\Json::class, [[]], '{bad', 'Invalid JSON.'],
            'json valid'                              => [V\Json::class, [[]], '{"a":1}'],

            'ip v4 valid'                             => [V\Ip::class, [V\Ip::V4], '127.0.0.1'],
            'ip v6 invalid'                           => [V\Ip::class, [V\Ip::V6], '127.0.0.1', 'Invalid IP address.'],
            'ip invalid version config'               => [V\Ip::class, ['nope'], '127.0.0.1', "Ip validator: unknown version 'nope'.", InvalidArgumentException::class],
            'ip empty versions config'                => [V\Ip::class, [[]], '127.0.0.1', 'Ip validator requires at least one version.', InvalidArgumentException::class],
            'bic valid'                               => [V\Bic::class, [], 'DEUTDEFF'],
            'bic invalid'                             => [V\Bic::class, [], 'INVALID', 'Invalid BIC.'],
            'card scheme visa valid'                  => [V\CardScheme::class, ['visa'], '4111111111111111'],
            'card scheme invalid'                     => [V\CardScheme::class, ['visa'], '1234', 'Invalid card scheme.'],
            'card scheme empty config'                => [V\CardScheme::class, [[]], '4111111111111111', 'CardScheme validator requires at least one scheme.', InvalidArgumentException::class],
            'card scheme non string config'           => [V\CardScheme::class, [[new \stdClass()]], '4111111111111111', 'CardScheme validator expects scheme names as strings.', InvalidArgumentException::class],
            'card scheme unknown config'              => [V\CardScheme::class, ['nope'], '4111111111111111', "CardScheme validator: unknown scheme 'nope'.", InvalidArgumentException::class],
            'currency valid'                          => [V\Currency::class, [], 'USD'],
            'currency invalid'                        => [V\Currency::class, [], 'US', 'Invalid currency.'],
            'luhn valid'                              => [V\Luhn::class, [], '79927398713'],
            'luhn invalid'                            => [V\Luhn::class, [], '79927398714', 'Invalid checksum.'],
            'iban valid'                              => [V\Iban::class, [], 'GB82WEST12345698765432'],
            'iban invalid'                            => [V\Iban::class, [], 'GB82WEST12345698765433', 'Invalid IBAN.'],
            'iban invalid country code config'        => [V\Iban::class, ['G1'], 'GB82WEST12345698765432', 'Iban validator: country code must be a 2-letter ISO code.', InvalidArgumentException::class],
            'iban invalid length'                     => [V\Iban::class, [], 'GB82WEST123', 'Invalid IBAN.'],
            'iban invalid format'                     => [V\Iban::class, [], 'GB82WEST12345698$%432', 'Invalid IBAN.'],
            'iban country mismatch'                   => [V\Iban::class, ['DE'], 'GB82WEST12345698765432', 'Invalid IBAN.'],
            'isbn valid'                              => [V\Isbn::class, [], '0-306-40615-2'],
            'isbn invalid'                            => [V\Isbn::class, [], '0-306-40615-3', 'Invalid ISBN.'],
            'isbn invalid length'                     => [V\Isbn::class, [], '123', 'Invalid ISBN.'],
            'isbn invalid type config'                => [V\Isbn::class, ['isbn42'], '0-306-40615-2', 'Isbn validator: type must be isbn10, isbn13, or null.', InvalidArgumentException::class],
            'isbn10 valid'                            => [V\Isbn::class, [V\Isbn::ISBN_10], '0-306-40615-2'],
            'isbn10 valid x'                          => [V\Isbn::class, [V\Isbn::ISBN_10], '0-8044-2957-X'],
            'isbn13 valid'                            => [V\Isbn::class, [V\Isbn::ISBN_13], '9780306406157'],
            'issn valid'                              => [V\Issn::class, [], '0378-5955'],
            'issn valid check digit x'                => [V\Issn::class, [], '0000-006X'],
            'issn valid check digit zero'             => [V\Issn::class, [], '0000-0000'],
            'issn invalid'                            => [V\Issn::class, [], '0378-5956', 'Invalid ISSN.'],
            'issn invalid format'                     => [V\Issn::class, [], '12345-67A', 'Invalid ISSN.'],
            'luhn non-digit invalid'                  => [V\Luhn::class, [], '----', 'Invalid checksum.'],
            'json invalid type config'                => [V\Json::class, [['invalid']], '{}', "Json validator: unknown JSON type 'invalid'.", InvalidArgumentException::class],
            'json valid bool type'                    => [V\Json::class, [['bool']], 'false'],
            'json valid null type'                    => [V\Json::class, [['null']], 'null'],
            'enum backed value invalid enum'          => [V\EnumBackedValue::class, [\stdClass::class], 'draft', 'EnumBackedValue validator expects a BackedEnum class, got stdClass.', InvalidArgumentException::class],

            'enum backed value invalid value'         => [V\EnumBackedValue::class, [DummyStatus::class], 'nope', 'Expected a valid enum backing value, got a string.'],
            'enum backed value valid'                 => [V\EnumBackedValue::class, [DummyStatus::class], 'draft'],
            'enum case invalid class'                 => [V\EnumCase::class, [\stdClass::class], 'draft', 'EnumCase validator expects an enum class, got stdClass.', InvalidArgumentException::class],
            'enum case invalid string'                => [V\EnumCase::class, [DummyStatus::class], 'archived', 'Invalid enum case.'],
            'enum case valid instance'                => [V\EnumCase::class, [DummyStatus::class], DummyStatus::Published],
            'enum case valid string'                  => [V\EnumCase::class, [DummyStatus::class], 'draft'],
            'compare to equal'                        => [V\CompareTo::class, ['==', 5], 5],
            'compare to failed'                       => [V\CompareTo::class, ['==', 5], 6, "'==' comparison failed."],
            'compare to invalid operator'             => [V\CompareTo::class, ['?=', 5], 5, "CompareTo validator: invalid operator '?='.", InvalidArgumentException::class],
            'compare to backed enum'                  => [V\CompareTo::class, ['===', DummyStatus::Draft], DummyStatus::Draft],
            'compare to datetime string'              => [V\CompareTo::class, ['==', new \DateTimeImmutable('2020-01-01')], '2020-01-01'],
            'compare to invalid datetime string'      => [V\CompareTo::class, ['==', new \DateTimeImmutable('2020-01-01')], 'not-a-date', 'Expected a date and time, got an invalid string.'],
            'compare to invalid scalar datetime'      => [V\CompareTo::class, ['==', 'not-a-date'], new \DateTimeImmutable('2020-01-01'), "CompareTo validator: scalar 'not-a-date' is not a valid datetime.", InvalidArgumentException::class],
            'compare to unit enum invalid operator'   => [V\CompareTo::class, ['<', DummyUnitStatus::Alpha], DummyUnitStatus::Beta, "CompareTo validator: operator '<' is not supported for unit enums.", InvalidArgumentException::class],
            'compare to unit enum equality'           => [V\CompareTo::class, ['==', DummyUnitStatus::Alpha], DummyUnitStatus::Alpha],
            'compare to less than'                    => [V\CompareTo::class, ['<', 5], 3],
            'compare to less or equal'                => [V\CompareTo::class, ['<=', 5], 5],
            'compare to greater than'                 => [V\CompareTo::class, ['>', 5], 7],
            'compare to greater or equal'             => [V\CompareTo::class, ['>=', 5], 5],
            'compare to not equal op'                 => [V\CompareTo::class, ['!=', 5], 4],
            'compare to not equal op loose equal'     => [V\CompareTo::class, ['!=', 5], '5', "'!=' comparison failed."],
            'compare to not identical'                => [V\CompareTo::class, ['!==', 5], '5'],

            'in coercive'                             => [V\In::class, [['1'], false], 1],
            'in empty'                                => [V\In::class, [[]], 'anything', 'In validator requires at least one choice.', InvalidArgumentException::class],
            'in invalid'                              => [V\In::class, [['yes', 'no']], 'maybe', 'Value must be one of yes or no.'],
            'in valid'                                => [V\In::class, [['yes', 'no']], 'yes'],
            'instance of empty class'                 => [V\IsInstanceOf::class, [''], new \stdClass(), 'IsInstanceOf validator requires a class or interface name.', InvalidArgumentException::class],
            'instance of invalid class name'          => [V\IsInstanceOf::class, ['not-a-class'], new \stdClass(), "Class or interface 'not-a-class' does not exist.", InvalidArgumentException::class],
            'instance of invalid'                     => [V\IsInstanceOf::class, [\stdClass::class], 'nope', 'Expected instance of stdClass, got a string'],
            'instance of valid'                       => [V\IsInstanceOf::class, [\stdClass::class], new \stdClass()],
            'is type array invalid'                   => [V\IsType::class, ['array'], 'not-array', 'Expected an array, got a string.'],
            'is type array valid'                     => [V\IsType::class, ['array'], [1, 2]],
            'is type empty list'                      => [V\IsType::class, [[]], 'x', 'IsType validator requires at least one type.', InvalidArgumentException::class],
            'is type float invalid string'            => [V\IsType::class, ['float'], '1.25', 'Expected a float, got a string.'],
            'is type float valid'                     => [V\IsType::class, ['float'], 1.5],
            'is type int invalid string'              => [V\IsType::class, ['int'], '456', 'Expected an integer, got a string.'],
            'is type int valid'                       => [V\IsType::class, ['int'], 123],
            'is type boolean valid'                   => [V\IsType::class, ['boolean'], true],
            'is type double valid'                    => [V\IsType::class, ['double'], 1.25],
            'is type long valid'                      => [V\IsType::class, ['long'], 9],
            'is type numeric invalid'                 => [V\IsType::class, ['numeric'], 'foo', 'Expected a number, got a string.'],
            'is type numeric valid string'            => [V\IsType::class, ['numeric'], '12.3'],
            'is type string valid'                    => [V\IsType::class, ['string'], 'ok'],
            'is type class-string invalid'            => [V\IsType::class, ['class-string'], 'NotAClass', 'Expected a valid class name, got a string.'],
            'is type class-string valid'              => [V\IsType::class, ['class-string'], \DateTimeImmutable::class],
            'is type any valid'                       => [V\IsType::class, [['int', 'float']], 123.0],
            'is type non string type'                 => [V\IsType::class, [['int', 123]], 1, 'IsType validator expects type names as strings.', InvalidArgumentException::class],
            'is type unknown type'                    => [V\IsType::class, ['weird'], 'x', "IsType validator: unknown type 'weird'.", InvalidArgumentException::class],
            'is type scalar valid'                    => [V\IsType::class, ['scalar'], 'x'],
            'is type iterable valid'                  => [V\IsType::class, ['iterable'], new \ArrayIterator([1])],
            'is type countable valid'                 => [V\IsType::class, ['countable'], new \ArrayObject([1])],
            'is type callable valid'                  => [V\IsType::class, ['callable'], static fn (): bool => true],
            'is type object valid'                    => [V\IsType::class, ['object'], new \stdClass()],
            'is type resource valid'                  => [V\IsType::class, ['resource'], self::resourceHandle()],
            'is type null valid'                      => [V\IsType::class, ['null'], null],
            'is numeric string invalid'               => [V\IsNumericString::class, [], 123, 'Expected a numeric string, got an integer.'],
            'is numeric string valid'                 => [V\IsNumericString::class, [], '123.4'],
            'is blank true null'                      => [V\IsBlank::class, [true], null],
            'is blank true whitespace'                => [V\IsBlank::class, [true], '   '],
            'is blank true array'                     => [V\IsBlank::class, [true], []],
            'is blank true countable'                 => [V\IsBlank::class, [true], new \ArrayObject([])],
            'is blank false countable'                => [V\IsBlank::class, [false], new \ArrayObject([]), 'Expected a non-blank value, got an empty countable.'],
            'is blank false iterable'                 => [V\IsBlank::class, [false], self::nonEmptyIterator()],
            'is blank true int zero invalid'          => [V\IsBlank::class, [true], 0, 'Expected a blank value, got an integer.'],
            'is blank true zero invalid'              => [V\IsBlank::class, [true], '0', 'Expected a blank value, got a non-empty string.'],
            'is blank false invalid'                  => [V\IsBlank::class, [false], '   ', 'Expected a non-blank value, got an empty string.'],
            'is blank false valid'                    => [V\IsBlank::class, [false], 'foo'],
            'is null true valid'                      => [V\IsNull::class, [true], null],
            'is null true invalid'                    => [V\IsNull::class, [true], 'ok', 'Expected a null value, got a string.'],
            'is null false invalid'                   => [V\IsNull::class, [false], null, 'Expected a non-null value, got a null value.'],
            'is null false valid'                     => [V\IsNull::class, [false], 'ok'],
            'contained in array valid'                => [V\ContainedIn::class, [[1, 2, 3], null], [2, 3]],
            'contained in string valid'               => [V\ContainedIn::class, ['foobar', null], 'foo'],
            'contained in string at index'            => [V\ContainedIn::class, ['barfoo', 3], 'foo'],
            'contained in string at negative index'   => [V\ContainedIn::class, ['barfooxx', -2], 'foo'],
            'contained in string at index oob'        => [V\ContainedIn::class, ['barfoo', 10], 'foo', 'Value is not contained in the allowed set.'],
            'contained in string at negative oob 1'   => [V\ContainedIn::class, ['barfoo', -3], '.bar', 'Value is not contained in the allowed set.'],
            'contained in string at negative oob 2'   => [V\ContainedIn::class, ['barfoo', -10], 'foo', 'Value is not contained in the allowed set.'],
            'contained in string invalid'             => [V\ContainedIn::class, ['foobar', null], 'nope', 'Value is not contained in the allowed set.'],
            'contained in string type mismatch'       => [V\ContainedIn::class, [[1, 2, 3], null], 'a', 'Expected a traversable value'],
            'contained in array invalid'              => [V\ContainedIn::class, [[1, 2, 3], null], [3, 2], 'Value is not contained in the allowed set.'],
            'contained in type mismatch'              => [V\ContainedIn::class, ['abc', null], ['a'], 'Expected a traversable value'],
            'contained in non iterable value'         => [V\ContainedIn::class, [[1, 2, 3], null], 123, 'Expected a traversable value'],
            'contained in non rewindable'             => [V\ContainedIn::class, [[1, 2, 3], null], self::nonRewindableIterator(), 'Iterable value is not rewindable.'],
            'contained in non countable negative'     => [V\ContainedIn::class, [self::nonRewindableIterator(), -1], [1], "ContainedIn validator: negative '\$at' requires a countable iterable.", InvalidArgumentException::class],
            'contained in array start'                => [V\ContainedIn::class, [[1, 2, 3], 'start'], [1, 2]],
            'contained in array end'                  => [V\ContainedIn::class, [[1, 2, 3], 'end'], [2, 3]],
            'contained in array at index'             => [V\ContainedIn::class, [[1, 2, 3], 1], [2, 3]],
            'contained in array at negative index'    => [V\ContainedIn::class, [[1, 2, 3, 4, 5], -3], [1, 2]],
            'contained in array at index oob'         => [V\ContainedIn::class, [[1, 2, 3], 5], [2, 3], 'Value is not contained in the allowed set.'],
            'contained in array at negative oob 1'    => [V\ContainedIn::class, [[1, 2, 3], -5], [2, 3], 'Value is not contained in the allowed set.'],
            'contained in array at negative oob 2'    => [V\ContainedIn::class, [[1, 2, 3], -2], [1, 2], 'Value is not contained in the allowed set.'],
            'contained in empty needle'               => [V\ContainedIn::class, [[1, 2, 3], null], []],
            'contained in iterator valid'             => [V\ContainedIn::class, [new \ArrayIterator([1, 2, 3]), null], new \ArrayIterator([2, 3])],
            'contains string valid'                   => [V\Contains::class, ['foo', null], 'barfoo'],
            'contains string case insensitive'        => [V\Contains::class, ['Foo', null, false], 'barfoo'],
            'contains iterable case insensitive'      => [V\Contains::class, [[1, 2], null, false], [1, 2], 'Contains validator: caseSensitive=false requires a string needle.', InvalidArgumentException::class],
            'contains string start invalid'           => [V\Contains::class, ['foo', 'start'], 'barfoo', 'Expected value to contain the given element.'],
            'contains string end valid'               => [V\Contains::class, ['foo', 'end'], 'barfoo'],
            'contains string at index'                => [V\Contains::class, ['foo', 3], 'barfoo'],
            'contains string at negative index'       => [V\Contains::class, ['foo', -3], 'fooxxx'],
            'contains string at index oob'            => [V\Contains::class, ['foo', 10], 'barfoo', 'Expected value to contain the given element.'],
            'contains string at negative oob'         => [V\Contains::class, ['foo', -10], 'barfoo', 'Expected value to contain the given element.'],
            'contains empty needle'                   => [V\Contains::class, ['', null], 'bar'],
            'contains invalid position'               => [V\Contains::class, ['foo', 'middle'], 'barfoo', "Contains validator: invalid 'at' position 'middle'.", InvalidArgumentException::class],
            'contains type mismatch'                  => [V\Contains::class, [['a'], null], 'abc', 'Expected a traversable value'],
            'contains iterable type mismatch'         => [V\Contains::class, ['a', null], [1, 2, 3], 'Expected a traversable value'],
            'contains non rewindable'                 => [V\Contains::class, [self::nonRewindableIterator(), null], [1, 2, 3], 'Iterable value is not rewindable.'],
            'contains non countable negative'         => [V\Contains::class, [self::nonRewindableIterator(), -1], [1, 2, 3], "Contains validator: negative '\$at' requires a countable iterable.", InvalidArgumentException::class],
            'contains iterable not contained'         => [V\Contains::class, [[4, 5], null], [1, 2, 3], 'Expected value to contain the given element.'],
            'contains iterable at index'              => [V\Contains::class, [[2, 3], 1], [1, 2, 3]],
            'contains iterable at negative index'     => [V\Contains::class, [[1, 2], -3], [1, 2, 3, 4, 5]],
            'contains iterable at index oob'          => [V\Contains::class, [[2, 3], 5], [1, 2, 3], 'Expected value to contain the given element.'],
            'contains iterable at negative oob'       => [V\Contains::class, [[2, 3], -5], [1, 2, 3], 'Expected value to contain the given element.'],
            'contains iterator valid'                 => [V\Contains::class, [[2, 3], null], new \ArrayIterator([1, 2, 3])],
            'contains non iterable value'             => [V\Contains::class, ['foo', null], 123, 'Expected a traversable value'],
            'length array too short'                  => [V\Length::class, ['min' => 3], ['a'], 'Array must contain at least'],
            'length missing bounds'                   => [V\Length::class, [], 'abcd', 'Length validator requires at least one of min or max.', InvalidArgumentException::class],
            'length not a string'                     => [V\Length::class, ['min' => 2, 'max' => 6], 12345, 'Expected a string or an array, got an integer.'],
            'length on array'                         => [V\Length::class, ['min' => 2, 'max' => 3], ['a', 'b'], null],
            'length too long'                         => [V\Length::class, ['min' => 2, 'max' => 6], 'abcdefgh', 'String must be at most'],
            'length too short'                        => [V\Length::class, ['min' => 2, 'max' => 6], 'a', 'String must be at least'],
            'length valid'                            => [V\Length::class, ['min' => 2, 'max' => 6], 'abcd'],
            'range exclusive max'                     => [V\Range::class, ['max' => 1, 'inclusive' => false], 1, 'Number must be less than', GuardException::class],
            'range exclusive min'                     => [V\Range::class, ['min' => 1, 'inclusive' => false], 1, 'Number must be greater than', GuardException::class],
            'range above max'                         => [V\Range::class, ['min' => 1, 'max' => 5], 9, 'Number must be less than', GuardException::class],
            'range below min'                         => [V\Range::class, ['min' => 1, 'max' => 5], 0, 'Number must be greater than', GuardException::class],
            'range missing bounds'                    => [V\Range::class, [], 1, 'Range validator requires at least one of min or max.', InvalidArgumentException::class],
            'range invalid bounds'                    => [V\Range::class, ['min' => 5, 'max' => 1], 1, 'Range validator requires min to be less than or equal to max.', InvalidArgumentException::class],
            'range non numeric value'                 => [V\Range::class, ['min' => 1, 'max' => 5], 'foo', 'Expected an integer or a float, got a string.'],
            'range valid'                             => [V\Range::class, ['min' => 1, 'max' => 5], 3],
            'regex invalid'                           => [V\Regex::class, ['/^[a-z]{3}[0-9]{3}$/'], 'xyz', 'Value does not match the required pattern.'],
            'regex negate invalid'                    => [V\Regex::class, ['/^foo$/', true], 'foo', 'Value matches a forbidden pattern.'],
            'regex negate valid'                      => [V\Regex::class, ['/^foo$/', true], 'bar'],
            'regex numeric val converts to string'    => [V\Regex::class, ['/^\d{3}$/'], 123],
            'regex valid'                             => [V\Regex::class, ['/^[a-z]{3}[0-9]{3}$/'], 'abc123'],
            'url invalid scheme'                      => [V\Url::class, [], 'ftp://example.com', 'Invalid URL scheme.'],
            'url valid with custom scheme'            => [V\Url::class, ['scheme' => 'ftp'], 'ftp://example.com'],
            'url required scheme missing'             => [V\Url::class, [], 'example.com', 'URL is missing a scheme.'],
            'url not-required scheme missing'         => [V\Url::class, ['scheme' => [], 'require' => []], '//example.com'],
            'url non string converted to string'      => [V\Url::class, ['scheme' => [], 'require' => []], 42],
            'url valid no scheme'                     => [V\Url::class, ['scheme' => [], 'require' => []], 'ftp://example.com'],
            'url valid'                               => [V\Url::class, [], 'https://example.com'],
            'url invalid host'                        => [V\Url::class, [], 'https://exa mple.com', 'Invalid URL host.'],
            'url parse failure'                       => [V\Url::class, [], 'https:///example.com', 'Invalid URL.'],
            'url appends scheme requirement'          => [V\Url::class, ['scheme' => 'ftp', 'require' => ['host']], 'ftp://example.com'],
            'uuid invalid'                            => [V\Uuid::class, [], 'not-a-uuid', 'Invalid UUID.'],
            'uuid valid'                              => [V\Uuid::class, [], '123e4567-e89b-12d3-a456-426614174000'],
            'regex empty pattern'                     => [V\Regex::class, [''], 'anything', 'Regex validator requires a pattern.', InvalidArgumentException::class],
            'regex invalid pattern'                   => [V\Regex::class, ['/[a-z'], 'abc', 'Regex validator: invalid pattern //[a-z/', InvalidArgumentException::class],
            'length range mismatch'                   => [V\Length::class, ['min' => 5, 'max' => 1], 'four', 'Length validator requires min to be less than or equal to max.', InvalidArgumentException::class],
            'in json encode failure'                  => [V\In::class, [self::resourceList()], 'nope', 'Value must be one of'],
        ];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testJsonPolyfillValidation(): void
    {
        $method = new \ReflectionMethod(V\Json::class, 'polyfillJsonValidate');
        /** @psalm-suppress UnusedMethodCall */
        $method->setAccessible(true);

        self::assertTrue($method->invoke(null, '{"a":1}'));
        self::assertFalse($method->invoke(null, '{bad'));
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testJsonDetectType(): void
    {
        $method = new \ReflectionMethod(V\Json::class, 'detectType');
        /** @psalm-suppress UnusedMethodCall */
        $method->setAccessible(true);

        self::assertNull($method->invoke(null, ''));
        self::assertSame('bool', $method->invoke(null, 'true'));
        self::assertSame('null', $method->invoke(null, 'null'));
        self::assertSame('number', $method->invoke(null, '-1'));
    }

    /** @psalm-suppress PossiblyUnusedMethod, MixedAssignment */
    public function testIpFlagsForVersion(): void
    {
        $method = new \ReflectionMethod(V\Ip::class, 'flagsForVersion');
        /** @psalm-suppress UnusedMethodCall */
        $method->setAccessible(true);

        $flags = $method->invoke(null, V\Ip::V4_NO_PRIV);
        self::assertSame(FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE, $flags);

        $flags = $method->invoke(null, V\Ip::V6_NO_RES);
        self::assertSame(FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE, $flags);

        $flags = $method->invoke(null, V\Ip::ALL_NO_PRIV);
        self::assertSame(FILTER_FLAG_NO_PRIV_RANGE, $flags);

        $flags = $method->invoke(null, V\Ip::ALL_NO_RES);
        self::assertSame(FILTER_FLAG_NO_RES_RANGE, $flags);
    }

    private static function resourceList(): array
    {
        return [fopen('php://memory', 'r')];
    }

    private static function nonRewindableIterator(): \Generator
    {
        yield 1;
        yield 2;
    }

    private static function nonEmptyIterator(): \Generator
    {
        yield 'value';
    }

    /** @return false|resource */
    private static function resourceHandle()
    {
        return fopen('php://memory', 'r');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testIsInstanceOfValidatorErrorMessageClassParameter(): void
    {
        $dto = new class extends FullDto {
            #[V\IsInstanceOf(BuiltInValidatorClassesTest::class)]
            public mixed $testCase = null;
        };

        // In dev mode, the error message should contain the full class name
        try {
            $dto->loadArray(['testCase' => new \stdClass()]);
            $this->fail('Expected exception was not thrown.');
        } catch (GuardException $e) {
            self::assertSame(
                BuiltInValidatorClassesTest::class,
                $e->getMessageParameters()['class'],
            );
        }

        // In production mode, the error message should contain only the short class name
        try {
            ProcessingContext::setDevMode(false);
            $dto->loadArray(['testCase' => new \stdClass()]);
            $this->fail('Expected exception was not thrown.');
        } catch (GuardException $e) {
            self::assertSame(
                'BuiltInValidatorClassesTest',
                $e->getMessageParameters()['class'],
            );
        } finally {
            ProcessingContext::setDevMode(true);
        }
    }
}
