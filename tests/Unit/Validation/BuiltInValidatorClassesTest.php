<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ValidateBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\DtoToolkit\Validate as V;
use PHPUnit\Framework\TestCase;

enum DummyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

final class BuiltInValidatorClassesTest extends TestCase
{
    /**
     * @dataProvider validatorProvider
     *
     * @param class-string<ValidateBase>|ValidateBase $validatorClass
     * @param class-string<\Throwable>                $exceptionClass
     */
    public function testValidatorBehavior(
        string | ValidateBase $validatorClass,
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
        if ($validatorClass instanceof ValidateBase) {
            $validatorClass->args = $params;
            $validator = $validatorClass->getProcessingNode($dto);
        } elseif (is_subclass_of($validatorClass, ValidateBase::class, true)) {
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

        ProcessingNodeBase::setCurrentPropName('test');
        ProcessingNodeBase::setCurrentDto($dto);
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
    }

    public static function validatorProvider(): array
    {
        return [
            'date format empty'                      => [V\DateFormat::class, [''], '2025-01-02', 'DateFormat validator requires a format string.', InvalidConfigException::class],
            'date format invalid'                    => [V\DateFormat::class, ['Y-m-d'], '2025/99/99', 'processing.guard.date.format_mismatch'],
            'date format non string'                 => [V\DateFormat::class, ['Y-m-d'], 123, 'processing.guard.date.format_mismatch'],
            'date format valid'                      => [V\DateFormat::class, ['Y-m-d'], '2025-01-02'],
            'email invalid'                          => [V\Email::class, [], 'not-an-email', 'processing.guard.email.invalid'],
            'email valid'                            => [V\Email::class, [], 'test@example.com'],
            'enum backed value invalid enum'         => [V\EnumBackedValue::class, [\stdClass::class], 'draft', 'EnumBackedValue validator expects a BackedEnum class, got stdClass.', InvalidConfigException::class],
            'enum backed value invalid instance'     => [V\EnumBackedValue::class, [DummyStatus::class], DummyStatus::Draft, 'must be a backing value of '.DummyStatus::class],
            'enum backed value invalid value'        => [V\EnumBackedValue::class, [DummyStatus::class], 'nope', 'must be a backing value of '.DummyStatus::class],
            'enum backed value valid'                => [V\EnumBackedValue::class, [DummyStatus::class], 'draft'],
            'enum case invalid class'                => [V\EnumCase::class, [\stdClass::class], 'draft', 'EnumCase validator expects an enum class, got stdClass.', InvalidConfigException::class],
            'enum case invalid string'               => [V\EnumCase::class, [DummyStatus::class], 'archived', 'processing.guard.enum.invalid_case'],
            'enum case valid instance'               => [V\EnumCase::class, [DummyStatus::class], DummyStatus::Published],
            'enum case valid string'                 => [V\EnumCase::class, [DummyStatus::class], 'draft'],
            'in array coercive'                      => [V\InArray::class, [['1'], false], 1],
            'in array empty'                         => [V\InArray::class, [[]], 'anything', 'InArray validator requires at least one choice.', InvalidConfigException::class],
            'in array invalid'                       => [V\InArray::class, [['yes', 'no']], 'maybe', 'processing.guard.in_array.not_allowed'],
            'in array valid'                         => [V\InArray::class, [['yes', 'no']], 'yes'],
            'instance of empty class'                => [V\InstanceOfClass::class, [''], new \stdClass(), 'InstanceOfClass validator requires a class name.', InvalidConfigException::class],
            'instance of invalid'                    => [V\InstanceOfClass::class, [\stdClass::class], 'nope', 'processing.guard.not_instance_of'],
            'instance of valid'                      => [V\InstanceOfClass::class, [\stdClass::class], new \stdClass()],
            'is array invalid'                       => [V\IsArray::class, [], 'not-array', 'must be an array'],
            'is array valid'                         => [V\IsArray::class, [], [1, 2]],
            'is float invalid'                       => [V\IsFloat::class, [], 'abc', 'must be a float'],
            'is float valid string'                  => [V\IsFloat::class, [], '1.25'],
            'is float valid'                         => [V\IsFloat::class, [], 1.5],
            'is integer invalid'                     => [V\IsInteger::class, [], '12.3', 'must be an integer'],
            'is integer valid int float'             => [V\IsInteger::class, [], 123.0],
            'is integer valid int'                   => [V\IsInteger::class, [], 123],
            'is integer valid string'                => [V\IsInteger::class, [], '456'],
            'is numeric invalid'                     => [V\IsNumeric::class, [], 'foo', 'must be numeric'],
            'is numeric string invalid'              => [V\IsNumericString::class, [], 123, 'must be a numeric string'],
            'is numeric string valid'                => [V\IsNumericString::class, [], '123.4'],
            'is numeric valid float'                 => [V\IsNumeric::class, [], '12.3'],
            'is numeric valid int'                   => [V\IsNumeric::class, [], 123],
            'length array too short'                 => [V\Length::class, ['min' => 3], ['a'], 'processing.guard.array.length_below_min'],
            'length missing bounds'                  => [V\Length::class, [], 'abcd', 'Length validator requires at least one of min or max.', InvalidArgumentException::class],
            'length not a string'                    => [V\Length::class, ['min' => 2, 'max' => 6], 12345, 'processing.guard.expected'],
            'length on array'                        => [V\Length::class, ['min' => 2, 'max' => 3], ['a', 'b'], null],
            'length too long'                        => [V\Length::class, ['min' => 2, 'max' => 6], 'abcdefgh', 'processing.guard.string.length_above_max'],
            'length too short'                       => [V\Length::class, ['min' => 2, 'max' => 6], 'a', 'processing.guard.string.length_below_min'],
            'length valid'                           => [V\Length::class, ['min' => 2, 'max' => 6], 'abcd'],
            'not blank invalid'                      => [V\NotBlank::class, [], '   ', 'processing.guard.not_blank'],
            'not blank non stringable'               => [V\NotBlank::class, [], [], 'processing.guard.stringable.expected'], // must be a string/stringable to check blank
            'not blank untrimmed'                    => [V\NotBlank::class, [false], '', 'processing.guard.not_blank'],
            'not blank valid'                        => [V\NotBlank::class, [], 'foo'],
            'not null invalid'                       => [V\NotNull::class, [], null, 'processing.guard.not_null'],
            'not null valid'                         => [V\NotNull::class, [], 'ok'],
            'range exclusive max'                    => [V\Range::class, ['max' => 1, 'inclusive' => false], 1, 'number.above_max', GuardException::class],
            'range exclusive min'                    => [V\Range::class, ['min' => 1, 'inclusive' => false], 1, 'number.below_min', GuardException::class],
            'range above max'                        => [V\Range::class, ['min' => 1, 'max' => 5], 9, 'number.above_max', GuardException::class],
            'range below min'                        => [V\Range::class, ['min' => 1, 'max' => 5], 0, 'number.below_min', GuardException::class],
            'range missing bounds'                   => [V\Range::class, [], 1, 'Range validator requires at least one of min or max.', InvalidConfigException::class],
            'range invalid bounds'                   => [V\Range::class, ['min' => 5, 'max' => 1], 1, 'Range validator requires min to be less than or equal to max.', InvalidArgumentException::class],
            'range non numeric value'                => [V\Range::class, ['min' => 1, 'max' => 5], 'foo', 'processing.guard.expected'],
            'range valid'                            => [V\Range::class, ['min' => 1, 'max' => 5], 3],
            'regex invalid'                          => [V\Regex::class, ['/^[a-z]{3}[0-9]{3}$/'], 'xyz', 'processing.guard.regex.no_match'],
            'regex negate invalid'                   => [V\Regex::class, ['/^foo$/', true], 'foo', 'processing.guard.regex.match_forbidden'],
            'regex negate valid'                     => [V\Regex::class, ['/^foo$/', true], 'bar'],
            'regex numeric val converts to string'   => [V\Regex::class, ['/^\d{3}$/'], 123],
            'regex valid'                            => [V\Regex::class, ['/^[a-z]{3}[0-9]{3}$/'], 'abc123'],
            'url invalid scheme'                     => [V\Url::class, [], 'ftp://example.com', 'processing.guard.invalid_url_scheme'],
            'url valid with custom scheme'           => [V\Url::class, ['scheme' => 'ftp'], 'ftp://example.com'],
            'url required scheme missing'            => [V\Url::class, [], 'example.com', 'url_missing_scheme'],
            'url not-required scheme missing'        => [V\Url::class, ['scheme' => [], 'require' => []], '//example.com'],
            'url non string converted to string'     => [V\Url::class, ['scheme' => [], 'require' => []], 42],
            'url valid no scheme'                    => [V\Url::class, ['scheme' => [], 'require' => []], 'ftp://example.com'],
            'url valid'                              => [V\Url::class, [], 'https://example.com'],
            'url invalid host'                       => [V\Url::class, [], 'https://exa mple.com', 'processing.guard.url_invalid_host'],
            'url parse failure'                      => [V\Url::class, [], 'https:///example.com', 'processing.guard.invalid_url'],
            'url appends scheme requirement'         => [V\Url::class, ['scheme' => 'ftp', 'require' => ['host']], 'ftp://example.com'],
            'uuid invalid'                           => [V\Uuid::class, [], 'not-a-uuid', 'processing.guard.invalid_uuid'],
            'uuid valid'                             => [V\Uuid::class, [], '123e4567-e89b-12d3-a456-426614174000'],
            'regex empty pattern'                    => [V\Regex::class, [''], 'anything', 'Regex validator requires a pattern.', InvalidArgumentException::class],
            'regex invalid pattern'                  => [V\Regex::class, ['/[a-z'], 'abc', 'Regex validator: invalid pattern //[a-z/', InvalidArgumentException::class],
            'length range mismatch'                  => [V\Length::class, ['min' => 5, 'max' => 1], 'four', 'processing.guard.string.length_not_in_range'],
            'in array json encode failure'           => [V\InArray::class, [self::resourceList()], 'nope', 'processing.guard.in_array.not_allowed'],
        ];
    }

    private static function resourceList(): array
    {
        return [fopen('php://memory', 'r')];
    }
}
