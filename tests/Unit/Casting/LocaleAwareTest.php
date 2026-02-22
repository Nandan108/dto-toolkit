<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\CastTo\LocalizedNumber;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException as ConfigInvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class LocaleAwareTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    #[\Override]
    public function setUp(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testValueIsTransformedByLocaleAwareCasterWithLocaleValueProvided(): void
    {
        $dtoClass = new class extends FullDto {
            #[CastTo\Floating]
            #[LocalizedNumber(locale: 'fr_FR', style: \NumberFormatter::DECIMAL)]
            public int | string | null $number_fr = null;
        };

        $dto = $dtoClass::newFromArray([
            'number_fr' => '1234.56',
        ]);

        $this->assertSame("1\u{202F}234,56", $dto->number_fr);
    }

    public function testValueIsTransformedByLocalAwareCaster(): void
    {
        $dtoClass = new class extends FullDto {
            #[CastTo\Floating]
            #[LocalizedNumber(locale: 'fr_FR', style: \NumberFormatter::DECIMAL)]
            public int | string | null $number_fr = null;

            #[LocalizedNumber(locale: LocaleAwareTest_DeLocaleProvider::class, style: \NumberFormatter::DECIMAL)]
            public float | string | null $number_de = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_ValueDependent::class, style: \NumberFormatter::DECIMAL)]
            public int | string | null $low_number = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_ValueDependent::class, style: \NumberFormatter::DECIMAL)]
            public int | string | null $high_number = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int | string | null $num_en_US = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int | string | null $num_fr = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int | string | null $num_fr_FR = null;

            #[CastTo\Floating]
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int | string | null $num = null;

            #[CastTo\LocalizedCurrency(currency: 'CHF', locale: 'fr_CH')]
            public int | string | null $amount_chf = null;

            // #[CastTo\Floating]
            // #[CastTo\LocaleAwareCurrency(LocalAwareTestLocaleProvider_PropNameDependent::class, )]
            // public int|string|null $num_fr_FR = null;
        };

        $dto = $dtoClass::newFromArray([
            'number_fr'   => '1234.56',
            'number_de'   => 1234.56,
            'low_number'  => '4.56',
            'high_number' => '1234.56',
            'num_fr'      => '1234.56',
            'num_fr_FR'   => '1234.56',
            'num_en_US'   => '1234.56',
            'amount_chf'  => '1234.564',
        ]);

        $this->assertSame("1\u{202F}234,56", $dto->number_fr);
        $this->assertSame('1.234,56', $dto->number_de);
        $this->assertSame('4.56', $dto->low_number);
        $this->assertSame("1\u{202F}234,56", $dto->high_number);
        $this->assertSame("1\u{202F}234,56\u{A0}€", $dto->num_fr_FR);
        $this->assertSame("1\u{202F}234,56\u{A0}¤", $dto->num_fr);
        $this->assertSame('$1,234.56', $dto->num_en_US);
        $this->assertSame("1\u{202F}234.56\u{A0}CHF", $dto->amount_chf);

        try {

            $dto->unfill()->loadArray(['number_fr' => 'not-a-number']);

            $this->fail('Expected TransformException');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.expected', $e->getMessageTemplate());
            $this->assertSame('number_fr{CastTo\Floating}', $e->getPropertyPath());
        }

        try {

            $dto->unfill()->loadArray(['amount_chf' => 'not numeric']);

            $this->fail('Expected TransformException');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.expected', $e->getMessageTemplate());
            $this->assertSame('amount_chf{CastTo\LocalizedCurrency}', $e->getPropertyPath());
        }
    }

    public function testLocaleResolverCanUseGetLocaleMethodOnDto(): void
    {
        $dtoClass = new class extends FullDto {
            public string $locale = 'fr_FR';

            #[LocalizedNumber(style: \NumberFormatter::DECIMAL)]
            public float | string | null $number = null;

            public function getLocale(): string
            {
                return $this->locale;
            }
        };

        $dto = $dtoClass::newFromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234,56", $dto->number);

        try {
            $dto->locale = 'bad locale string';

            $dto->unfill()->loadArray(['number' => 'not-a-number']);
            $this->fail('Expected TransformException');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.expected', $e->getMessageTemplate());
            $this->assertSame('number{CastTo\LocalizedNumber}', $e->getPropertyPath());
        }
    }

    public function testValueIsTransformedByLocaleAwareCasterWithLocaleResolverProvided(): void
    {
        $dtoClass = new class extends FullDto {
            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocaleAwareTest_DeLocaleProvider::class, style: \NumberFormatter::DECIMAL)]
            public int | string | null $number_de = null;
        };

        $dto = $dtoClass::newFromArray([
            'number_de' => '1234.56',
        ]);

        $this->assertSame('1.234,56', $dto->number_de);
    }

    public function testLocaleAwareCastersAcceptArrayCallableProvider(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(locale: [LocaleAwareTest_ArrayCallableLocaleProvider::class, 'getLocale'], style: \NumberFormatter::DECIMAL)]
            public int | float | string | null $number = null;

            #[CastTo\LocalizedCurrency(currency: 'CHF', locale: [LocaleAwareTest_ArrayCallableLocaleProvider::class, 'getLocale'])]
            public int | float | string | null $amount = null;
        };

        $dto = $dtoClass::newFromArray([
            'number' => 1234.56,
            'amount' => 1234.56,
        ]);

        $numberFormatter = new \NumberFormatter('de_DE', \NumberFormatter::DECIMAL);
        $currencyFormatter = new \NumberFormatter('de_DE', \NumberFormatter::CURRENCY);

        $this->assertSame($numberFormatter->format(1234.56), $dto->number);
        $this->assertSame($currencyFormatter->formatCurrency(1234.56, 'CHF'), $dto->amount);
    }

    public function testLocaleAwareCasterAcceptsClosureInAttributeOnPhp85AndUp(): void
    {
        if (!self::supportsClosureInAttributeArguments()) {
            $this->markTestSkipped('This runtime does not support closures in attribute arguments.');
        }

        $fqcn = __NAMESPACE__.'\\LocaleAwareTest_ClosureAttributeDto';
        if (!class_exists($fqcn, false)) {
            eval(<<<'PHP'
namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\LocalizedNumber;
use Nandan108\DtoToolkit\Core\FullDto;

final class LocaleAwareTest_ClosureAttributeDto extends FullDto
{
    #[LocalizedNumber(locale: static function (): string { return 'de_DE'; }, style: \NumberFormatter::DECIMAL)]
    public int|float|string|null $number = null;
}
PHP
            );
        }

        /** @var class-string<FullDto> $fqcn */
        $dto = $fqcn::newFromArray(['number' => 1234.56]);
        $formatter = new \NumberFormatter('de_DE', \NumberFormatter::DECIMAL);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($formatter->format(1234.56), $dto->number);
    }

    private static function supportsClosureInAttributeArguments(): bool
    {
        static $supported = null;
        if (null !== $supported) {
            return $supported;
        }
        if (!function_exists('exec')) {
            return $supported = false;
        }

        $probe = <<<'PHP'
#[\Attribute]
final class AttrProbe
{
    public function __construct(public \Closure $provider) {}
}
#[AttrProbe(static function (): bool { return true; })]
final class AttrProbeSubject {}
PHP;

        $cmd = escapeshellarg(\PHP_BINARY).' -r '.escapeshellarg($probe).' 2>/dev/null';
        $exitCode = 1;
        /** @var list<string> $output */
        $output = [];
        exec($cmd, $output, $exitCode);

        return $supported = 0 === $exitCode;
    }

    public function testUsesLocaleProviderInvalidLocaleThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(locale: 'bad-locale', style: \NumberFormatter::DECIMAL)]
            public int | float | string | null $number = null;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Cannot resolve locale from "bad-locale"');

        $dtoClass::newFromArray(['number' => 1234.56]);
    }

    public function testUsesLocaleProviderTakesLocaleFromContext(): void
    {
        // Set the default locale to 'en_US'
        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('en_US');

        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::DECIMAL)]
            public float | string | null $amount = null;
        };

        // Pass a locale via context and check if it is used

        $dto = $dtoClass::newWithContext(['locale' => 'fr_CH']);

        $dto->loadArray(['amount' => 1234.56]);

        $this->assertSame("1\u{202F}234,56", $dto->amount);

        // remove locale from context and check if default locale is used

        $dto->unfill()->contextUnset('locale')->loadArray(['amount' => 1234.56]);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('1,234.56', $dto->amount);

        // Pass an invalid lcoale via context and check if exception is thrown
        try {

            $dto->unfill()->contextSet('locale', 'bad-locale')->loadArray(['amount' => 1234.56]);
            $this->fail('Expected TransformException');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('returned an invalid value "bad-locale"', $e->getMessage());
        }
    }

    public function testLocalizedNumberNonNumericStringThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber]
            public int | string | null $number = null;
        };

        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Expected a number, got a string.');
        $dtoClass::newFromArray(['number' => 'not-a-number']);
    }

    public function testInvalidLocaleProviderThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(\NumberFormatter::DECIMAL, locale: 'stdClass')]
            public int | float | string | null $number = null;
        };

        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Class stdClass does not have a getLocale() method.');

        $dtoClass::newFromArray(['number' => 1234.56]);
    }

    public function testLocaleResolverRejectsInvalidConstructorArgType(): void
    {
        $caster = new LocalizedNumber();
        $caster->constructorArgs = ['locale' => 123];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Parameter 'locale' constructorArg must resolve to string|callable|null");
        $caster->bootOnDto();
    }

    private function getMontaryDecimalSeparator(string $locale): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL);
    }

    public function testUsesLocaleProviderDefaultPath(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY)]
            public float | string | null $number = null;
        };

        $locale = 'fr_CH.UTF-8';
        $decSep = $this->getMontaryDecimalSeparator($locale);

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default($locale);
        $dto = $dtoClass::newFromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234{$decSep}56\u{A0}CHF", $dto->number);
    }

    public function testUsesLocaleProviderDefaultPathWithIntlExtension(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY)]
            public float | string | null $number = null;
        };

        $locale = 'fr_CH.UTF-8';
        $decSep = $this->getMontaryDecimalSeparator($locale);

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default($locale);
        $dto = $dtoClass::newFromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234{$decSep}56\u{A0}CHF", $dto->number);
    }

    public function testLocalizedNumberFormatterThrowsWhenPrecisionIsNegative(): void
    {
        $this->expectException(ConfigInvalidArgumentException::class);
        $this->expectExceptionMessage('Precision must be a non-negative integer.');

        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(locale: 'en_US', precision: -1)]
            public string | float | null $num = null;
        };

        $dtoClass::newFromArray(['num' => 10]);
    }
}

final class LocaleAwareTest_DeLocaleProvider
{
    public static function getLocale(): string
    {
        // This is a mock implementation. In a real scenario, you would return the appropriate
        // locale based on the value or context.
        // For example, you might check the user's preferences or the current locale settings.
        // Here, we are just returning 'de_DE' for demonstration purposes.
        return 'de_DE';
    }
}

final class LocalAwareTestLocaleProvider_FR
{
    public static function getLocale(): string
    {
        return 'fr_FR';
    }
}

final class LocalAwareTestLocaleProvider_ValueDependent
{
    public static function getLocale(mixed $value): string
    {
        return $value > 10 ? 'fr_FR' : 'en_US';
    }
}

final class LocalAwareTestLocaleProvider_PropNameDependent
{
    /** @psalm-suppress PossiblyUnusedMethod, UnusedParam */
    public static function getLocale(mixed $value, string $propName): string
    {
        if (preg_match('/_([a-z]{2}(_[A-Z]{2})?)$/', $propName, $matches)) {
            return $matches[1];
        }

        return 'en_US';
    }
}

final class LocaleAwareTest_ArrayCallableLocaleProvider
{
    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue, UnusedParam */
    public static function getLocale(mixed $value, ?string $prop = null, ?FullDto $dto = null): string
    {
        return 'de_DE';
    }
}
