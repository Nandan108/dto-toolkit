<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\Presence;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\CastTo\DateTime;
use Nandan108\DtoToolkit\CastTo\LocalizedDateTime;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

final class UsesParamResolverTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        PropAccess::bootDefaultResolvers();

        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testResolvingParamViaContext(): void
    {
        $date = new \DateTimeImmutable('2025-10-05 12:34');
        $locales = [
            'de_DE' => '05.10.25, 12:34',
            'en_US' => "10/5/25, 12:34\u{202F}PM",
            'fr_FR' => '05/10/2025 12:34',
        ];
        foreach ($locales as $locale => $expected) {
            $dto = UsesParamResolverDateTestDto::withContext(['locale' => $locale]);
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromArrayLoose(['dateContextLocale' => $date]);

            $actual = $dto->dateContextLocale;

            $this->assertSame($expected, $actual);
        }

        // test with invlid locale in context
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            UsesParamResolverDateTestDto::withContext(['locale' => 'not-valid'])
                ->fromArray(['dateContextLocale' => $date]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Prop dateContextLocale: Invalid locale "not-valid" in context key \'locale\' for', $e->getMessage());
        }

        // test with null locale in context
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            UsesParamResolverDateTestDto::withContext(['locale' => null])
                ->fromArray(['dateContextLocale' => $date]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Cannot resolve context key \'locale\' (no context set)', $e->getMessage());
        }

        // test with DTO using '<context' resolution but not implementing HasContextInterface
        try {
            UsesParamResolverDateTestDtoNeedsContextButGotNone::fromArray(['dateContextLocale' => $date]);
        } catch (InvalidConfigException $e) {
            $this->assertSame('To use \'<context\' as a parameter value, the DTO must implement HasContextInterface.', $e->getMessage());
        }
    }

    public function testResolvingParamViaDto(): void
    {
        $date = new \DateTimeImmutable('2025-10-05 12:34');
        $locales = [
            // 'de_DE' => '05.10.25, 12:34',
            // 'en_US' => "10/5/25, 12:34\u{202F}PM",
            'fr_FR' => '05/10/2025 12:34',
        ];
        $dto = UsesParamResolverDateTestDto::withContext(['locale' => 'fr_FR']);
        foreach ($locales as $locale => $expected) {
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->withLocale($locale)->fromArray(['dateDtoLocale' => $date]);
            $actual = $dto->dateDtoLocale;

            $this->assertSame($expected, $actual);
            // custom getter
            $this->assertSame('05.10.25 12:34', $dto->dateDtoFrCHLocale);
        }
    }

    public function testSadPaths(): void
    {
        // test calling getParamResolverConfig() without having called configureParamResolver()
        try {
            ProcessingNodeBase::setCurrentDto(new UsesParamResolverDateTestDtoNeedsContextButGotNone());
            new class extends DateTime {
                public function __construct()
                {
                    $this->getParamResolverConfig('locale');
                }
            };
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('configureParamResolver() must be called before resolveParamProvider()', $e->getMessage());
        }

        // test DTO using '<dto' for locale resolution but not implementing getLocale()
        $sadPathDto = new class extends FullDto {
            #[LocalizedDateTime(locale: '<dto')]
            public \DateTimeInterface | string | null $date = null;
        };
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            $sadPathDto->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        } catch (InvalidConfigException $e) {
            $this->assertSame('DTO does not have a getLocale() method.', $e->getMessage());
        }

        // class provider doesn'at have get$Param method
        // test with invlid locale in context
        try {
            $dto = new class extends FullDto {
                #[LocalizedDateTime(locale: TestCase::class)]
                public \DateTimeInterface | string | null $date = null;
            };
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        } catch (InvalidArgumentException $e) {
            $paramValueOrProviderClass = TestCase::class;
            $paramGetter = 'getLocale';
            $this->assertStringContainsString("Class $paramValueOrProviderClass does not have a $paramGetter() method.", $e->getMessage());
        }

        // class doesn't exist -- can't resolve
        try {
            $dto = new class extends FullDto {
                #[LocalizedDateTime(locale: 'not-a-valid-class-or-locale')]
                public \DateTimeInterface | string | null $date = null;
            };
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Cannot resolve locale from "not-a-valid-class-or-locale"', $e->getMessage());
        }
    }

    public function testResolvingParamFromContextWithOperator(): void
    {
        $dto = new class extends FullDto {
            #[Mod\ApplyNextIf('<context:locale'), CastTo\Uppercase]
            public ?string $value0 = null;

            #[Mod\ApplyNextIf('<context:locale=fr_FR'), CastTo\Uppercase]
            public ?string $value1 = null;

            #[Mod\ApplyNextIf('<context:locale=/^fr/'), CastTo\Uppercase]
            public ?string $value2 = null;

            #[Mod\ApplyNextIf('<context:locale=/CH$/'), CastTo\Uppercase]
            public ?string $value3 = null;

            #[Mod\ApplyNextIf('<context:locale=/_/'), CastTo\Uppercase]
            public ?string $value4 = null;
        };
        $input = array_fill_keys(['value0', 'value1', 'value2', 'value3', 'value4'], 'foo');

        $locales = [
            ''      => ['foo', 'foo', 'foo', 'foo', 'foo'],
            'fr'    => ['FOO', 'foo', 'FOO', 'foo', 'foo'],
            'fr_FR' => ['FOO', 'FOO', 'FOO', 'foo', 'FOO'],
            'fr_CH' => ['FOO', 'foo', 'FOO', 'FOO', 'FOO'],
            'de_CH' => ['FOO', 'foo', 'foo', 'FOO', 'FOO'],
            'de_DE' => ['FOO', 'foo', 'foo', 'foo', 'FOO'],
        ];
        foreach ($locales as $locale => $expected) {
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->withContext(['locale' => $locale])->fromArray($input);
            $this->assertSame($expected, array_values($dto->toArray()));
        }
    }

    public function testDynamicResolution(): void
    {
        // dynamic resolution ...

        // context
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = UsesParamResolverDateTestDtoDynamicLocalResolution::withContext(['locale' => 'fr_FR'])
            ->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        // test invalid locale in context
        try {
            // context
            /** @psalm-suppress UndefinedMagicMethod */
            UsesParamResolverDateTestDtoDynamicLocalResolution::withContext(['locale' => 'not-a-locale'])
                ->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
            $this->fail('Expected exception not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString("\$dto->getContext('locale') returned an invalid value", $e->getMessage());
        }

        // dto:  which is returned by $dto->getLocale()
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = UsesParamResolverDateTestDtoDynamicLocalResolution::withLocale('de_DE')
            ->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('05.10.25, 12:34', $dto->date);

        // fallback
        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_FR');
        $dto = new class extends FullDto {
            #[LocalizedDateTime]
            public \DateTimeInterface | string | null $date = null;
        };
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_FR');

        // sad path: invalid param value
        $dto = new class extends FullDto {
            #[LocalizedDateTime]
            public \DateTimeInterface | string | null $date = null;
        };
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        // sad path: no fallback - unable to resolve
    }
}

final class UsesParamResolverDateTestDto extends FullDto
{
    private string $defaultLocale = 'fr_FR';

    /** @psalm-suppress PossiblyUnusedProperty */
    #[LocalizedDateTime(locale: '<context')]
    public \DateTimeInterface | string | null $dateContextLocale = null;

    #[LocalizedDateTime(locale: '<dto')]
    public \DateTimeInterface | string | null $dateDtoLocale = null;

    #[MapFrom('dateDtoLocale')]
    #[Presence(PresencePolicy::NullMeansMissing)]
    #[LocalizedDateTime(locale: '<dto:getFrCHLocale')]
    public \DateTimeInterface | string | null $dateDtoFrCHLocale = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[LocalizedDateTime]
    public \DateTimeInterface | string | null $dateDynamicLocale = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getLocale(): string
    {
        return $this->defaultLocale;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getFrCHLocale(): string
    {
        return 'fr_CH';
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function _withLocale(string $locale): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }
}

final class UsesParamResolverDeLocaleProvider
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public static function getLocale(): string
    {
        return 'de_DE';
    }
}

final class UsesParamResolverDateTestDtoNeedsContextButGotNone extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[LocalizedDateTime(locale: '<context')]
    public \DateTimeInterface | string | null $dateContextLocale = null;
}

final class UsesParamResolverDateTestDtoDynamicLocalResolution extends FullDto
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    private string $defaultLocale = 'fr_FR';

    /** @psalm-suppress PossiblyUnusedProperty */
    #[LocalizedDateTime]
    public \DateTimeInterface | string | null $date = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getLocale(): string
    {
        return $this->defaultLocale;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function _withLocale(string $locale): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }
}
