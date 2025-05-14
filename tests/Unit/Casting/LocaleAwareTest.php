<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\CastTo\LocalizedNumber;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

/*
 TODO: Cover sad paths for UsesLocaleResolver

 // protected function resolveLocaleProvider(?string $localeOrProviderClass): \Closure
- throw new \RuntimeException('Caster must implement CastTo to use UsesLocaleResolver');
- throw new \InvalidArgumentException('Invalid locale provider class '.$localeOrProviderClass);
- throw new \RuntimeException(sprintf('No locale provider was configured and the "intl" extension is not loaded. Cannot resolve locale for %s.', static::class));
- return locale_get_default();

// protected function getLocale(mixed $value, ?string $localeOrProviderClass): string
- throw new \RuntimeException('resolveLocaleProvider() must be run before calling getLocale()');
- throw new \RuntimeException('Current property or DTO is not set'); // getLocale() was called directly

*/

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
            public int|string|null $number_fr = null;
        };

        $dto = $dtoClass::fromArray([
            'number_fr' => '1234.56',
        ]);

        $this->assertSame("1\u{202F}234,56", $dto->number_fr);
    }

    public function testValueIsTransformedByLocalAwareCaster(): void
    {
        $dtoClass = new class extends FullDto {
            #[CastTo\Floating]
            #[LocalizedNumber(locale: 'fr_FR', style: \NumberFormatter::DECIMAL)]
            public int|string|null $number_fr = null;

            #[LocalizedNumber(locale: LocaleAwareTest_DeLocaleProvider::class, style: \NumberFormatter::DECIMAL)]
            public float|string|null $number_de = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_ValueDependent::class, style: \NumberFormatter::DECIMAL)]
            public int|string|null $low_number = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_ValueDependent::class, style: \NumberFormatter::DECIMAL)]
            public int|string|null $high_number = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int|string|null $num_en_US = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int|string|null $num_fr = null;

            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocalAwareTestLocaleProvider_PropNameDependent::class, style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int|string|null $num_fr_FR = null;

            #[CastTo\Floating]
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY_ACCOUNTING)]
            public int|string|null $num = null;

            #[CastTo\LocalizedCurrency(currency: 'CHF', locale: 'fr_CH')]
            public int|string|null $amount_chf = null;

            // #[CastTo\Floating]
            // #[CastTo\LocaleAwareCurrency(LocalAwareTestLocaleProvider_PropNameDependent::class, )]
            // public int|string|null $num_fr_FR = null;
        };

        $dto = $dtoClass::fromArray([
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
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->unfill()->fromArray(['number_fr' => 'not-a-number']);

            $this->fail('Expected CastingException');
        } catch (CastingException $e) {
            $this->assertStringStartsWith('Expected numeric, but got string:', $e->getMessage());
        }

        try {
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->unfill()->fromArray(['amount_chf' => 'not numeric']);

            $this->fail('Expected CastingException');
        } catch (CastingException $e) {
            $this->assertStringContainsString('Value is not numeric', $e->getMessage());
        }
    }

    public function testLocaleResolverCanUseGetLocaleMethodOnDto(): void
    {
        $dtoClass = new class extends FullDto {
            public string $locale = 'fr_FR';

            #[LocalizedNumber(style: \NumberFormatter::DECIMAL)]
            public float|string|null $number = null;

            public function getLocale(): string
            {
                return $this->locale;
            }
        };

        $dto = $dtoClass::fromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234,56", $dto->number);

        try {
            $dto->locale = 'bad locale string';
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->unfill()->fromArray(['number' => 'not-a-number']);
            $this->fail('Expected CastingException');
        } catch (CastingException $e) {
            // $this->assertStringStartsWith('$dto->getContext(\'locale\') returned an invalid locale "bad-locale"', $e->getMessage());
            $this->assertStringStartsWith('Expected: numeric, but got non-numeric string', $e->getMessage());
        }
    }

    public function testValueIsTransformedByLocaleAwareCasterWithLocaleResolverProvided(): void
    {
        $dtoClass = new class extends FullDto {
            #[CastTo\Floating]
            #[LocalizedNumber(locale: LocaleAwareTest_DeLocaleProvider::class, style: \NumberFormatter::DECIMAL)]
            public int|string|null $number_de = null;
        };

        $dto = $dtoClass::fromArray([
            'number_de' => '1234.56',
        ]);

        $this->assertSame('1.234,56', $dto->number_de);
    }

    public function testUsesLocaleProviderInvalidLocaleThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(locale: 'bad-locale', style: \NumberFormatter::DECIMAL)]
            public int|string|null $number = null;
        };

        try {
            $dtoClass::fromArray(['number' => 1234.56]);
            $this->fail('Expected CastingException');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('Cannot resolve locale from "bad-locale"', $e->getMessage());
        }
    }

    public function testUsesLocaleProviderTakesLocaleFromContext(): void
    {
        // Set the default locale to 'en_US'
        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('en_US');

        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::DECIMAL)]
            public float|string|null $amount = null;
        };

        // Pass a locale via context and check if it is used
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = $dtoClass::withContext(['locale' => 'fr_CH']);

        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['amount' => 1234.56]);

        $this->assertSame("1\u{202F}234,56", $dto->amount);

        // remove locale from context and check if default locale is used
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->unfill()->unsetContext('locale')->fromArray(['amount' => 1234.56]);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('1,234.56', $dto->amount);

        // Pass an invalid lcoale via context and check if exception is thrown
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->unfill()->setContext('locale', 'bad-locale')->fromArray(['amount' => 1234.56]);
            $this->fail('Expected CastingException');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('$dto->getContext(\'locale\') returned an invalid value "bad-locale"', $e->getMessage());
        }
    }

    public function testLocalizedNumberNonNumericStringThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber]
            public int|string|null $number = null;
        };

        $this->expectException(CastingException::class);
        $this->expectExceptionMessage('Expected: numeric, but got non-numeric string:');
        $dtoClass::fromArray(['number' => 'not-a-number']);
    }

    public function testInvalidLocaleProviderThrows(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(\NumberFormatter::DECIMAL, locale: 'stdClass')]
            public int|string|null $number = null;
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class stdClass does not have a getLocale() method.');

        $dtoClass::fromArray(['number' => 1234.56]);
    }

    public function testUsesLocaleProviderDefaultPath(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY)]
            public float|string|null $number = null;
        };

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_CH.UTF-8');
        $dto = $dtoClass::fromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234,56\u{A0}CHF", $dto->number);
    }

    public function testUsesLocaleProviderDefaultPathWithIntlExtension(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(style: \NumberFormatter::CURRENCY)]
            public float|string|null $number = null;
        };

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_CH.UTF-8');
        $dto = $dtoClass::fromArray(['number' => 1234.56]);

        $this->assertSame("1\u{202F}234,56\u{A0}CHF", $dto->number);
    }

    public function testLocalizedNumberFormatterThrowsWhenPrecisionIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Precision must be a non-negative integer.');

        $dtoClass = new class extends FullDto {
            #[LocalizedNumber(locale: 'en_US', precision: -1)]
            public string|float|null $num = null;
        };

        $dtoClass::fromArray(['num' => 10]);
    }
}

final class LocaleAwareTest_DeLocaleProvider
{
    /** @psalm-suppress PossiblyUnusedMethod */
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
    /** @psalm-suppress PossiblyUnusedMethod */
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
