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
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Context\ContextException;
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
            $dto = UsesParamResolverDateTestDto::newWithContext(['locale' => $locale]);

            $dto->loadArrayLoose(['dateContextLocale' => $date]);

            $actual = $dto->dateContextLocale;

            $this->assertSame($expected, $actual);
        }

        // test with invlid locale in context
        try {

            UsesParamResolverDateTestDto::newWithContext(['locale' => 'not-valid'])
                ->loadArray(['dateContextLocale' => $date]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Prop dateContextLocale: Invalid locale "not-valid" in context key \'locale\' for', $e->getMessage());
        }

        // test with null locale in context
        try {

            UsesParamResolverDateTestDto::newWithContext(['locale' => null])
                ->loadArray(['dateContextLocale' => $date]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Cannot resolve context key \'locale\' (no context set)', $e->getMessage());
        }

        // test with DTO using '<context' resolution but not implementing HasContextInterface
        try {
            UsesParamResolverDateTestDtoNeedsContextButGotNone::newFromArray(['dateContextLocale' => $date]);
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
        $dto = UsesParamResolverDateTestDto::newWithContext(['locale' => 'fr_FR']);
        foreach ($locales as $locale => $expected) {

            $dto->withLocale($locale)->loadArray(['dateDtoLocale' => $date]);
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
            $dto = new UsesParamResolverDateTestDtoNeedsContextButGotNone();
            $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
            ProcessingContext::pushFrame($frame);
            try {
                new class extends DateTime {
                    public function __construct()
                    {
                        $this->getParamResolverConfig('locale');
                    }
                };
            } finally {
                ProcessingContext::popFrame();
            }
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('configureParamResolver() must be called before resolveParamProvider()', $e->getMessage());
        }

        // test DTO using '<dto' for locale resolution but not implementing getLocale()
        $sadPathDto = new class extends FullDto {
            #[LocalizedDateTime(locale: '<dto')]
            public \DateTimeInterface | string | null $date = null;
        };
        try {

            $sadPathDto->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
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

            $dto->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
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

            $dto->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Cannot resolve locale from "not-a-valid-class-or-locale"', $e->getMessage());
        }
    }

    public function testResolveParamRequiresPropPath(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $caster = new class extends DateTime {
                public function callResolve(mixed $value): mixed
                {
                    return $this->resolveParam('timezone', $value, null);
                }
            };

            $caster->bootOnDto();

            $this->expectException(ContextException::class);
            $this->expectExceptionMessage('prop path is not set');
            $caster->callResolve('2025-01-01T00:00:00+00:00');
        } finally {
            ProcessingContext::popFrame();
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
            $dto->withContext(['locale' => $locale])->loadArray($input);
            $this->assertSame($expected, array_values($dto->toArray()));
        }
    }

    public function testDynamicResolution(): void
    {
        // dynamic resolution ...

        // context

        $dto = UsesParamResolverDateTestDtoDynamicLocalResolution::newWithContext(['locale' => 'fr_FR'])
            ->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        // test invalid locale in context
        try {
            // context

            UsesParamResolverDateTestDtoDynamicLocalResolution::newWithContext(['locale' => 'not-a-locale'])
                ->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
            $this->fail('Expected exception not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString("\$dto->getContext('locale') returned an invalid value", $e->getMessage());
        }

        // dto:  which is returned by $dto->getLocale()
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = UsesParamResolverDateTestDtoDynamicLocalResolution::newWithLocale('de_DE')
            ->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('05.10.25, 12:34', $dto->date);

        // fallback
        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_FR');
        $dto = new class extends FullDto {
            #[LocalizedDateTime]
            public \DateTimeInterface | string | null $date = null;
        };

        $dto->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        /** @psalm-suppress UnusedFunctionCall */
        locale_set_default('fr_FR');

        // sad path: invalid param value
        $dto = new class extends FullDto {
            #[LocalizedDateTime]
            public \DateTimeInterface | string | null $date = null;
        };

        $dto->loadArray(['date' => new \DateTimeImmutable('2025-10-05 12:34')]);
        $this->assertSame('05/10/2025 12:34', $dto->date);

        // sad path: no fallback - unable to resolve
    }

    public function testCallableProviderFailureIsWrappedAsInvalidConfigException(): void
    {
        $dto = new UsesParamResolverCallableProviderFailureDto();

        try {
            $dto->loadArray([
                'date' => '2025-10-05T12:34:00+00:00',
            ]);
            $this->fail('Expected InvalidConfigException was not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString("Callable provider for 'timezone' failed", $e->getMessage());
            $previous = $e->getPrevious();
            $this->assertInstanceOf(\RuntimeException::class, $previous);
            $this->assertSame('boom-provider', $previous->getMessage());
        }
    }

    public function testResolveParamRejectsInvalidConstructorArgTypeInResolveFlow(): void
    {
        $dto = new class extends BaseDto {
        };
        ProcessingContext::wrapProcessing($dto, function () {
            $caster = new class extends DateTime {
                public function callResolveTimezone(string $value): mixed
                {
                    return $this->resolveParam('timezone', $value);
                }
            };
            $caster->bootOnDto();
            $caster->constructorArgs = ['timezone' => 123];

            $this->expectException(InvalidConfigException::class);
            $this->expectExceptionMessage("Parameter 'timezone' constructorArg must resolve to string|callable|null");
            $caster->callResolveTimezone('2025-10-05 12:34:00');
        });
    }

    public function testGetConstructorArgReturnsNullOutsideProcessingNodeBase(): void
    {
        $helper = new UsesParamResolverPlainHelper();
        $this->assertNull($helper->callGetConstructorArg('timezone'));
    }
}

final class UsesParamResolverDateTestDto extends FullDto
{
    private string $defaultLocale = 'fr_FR';

    #[LocalizedDateTime(locale: '<context')]
    public \DateTimeInterface | string | null $dateContextLocale = null;

    #[LocalizedDateTime(locale: '<dto')]
    public \DateTimeInterface | string | null $dateDtoLocale = null;

    #[MapFrom('dateDtoLocale')]
    #[Presence(PresencePolicy::NullMeansMissing)]
    #[LocalizedDateTime(locale: '<dto:getFrCHLocale')]
    public \DateTimeInterface | string | null $dateDtoFrCHLocale = null;

    #[LocalizedDateTime]
    public \DateTimeInterface | string | null $dateDynamicLocale = null;

    public function getLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getFrCHLocale(): string
    {
        return 'fr_CH';
    }

    public function withLocale(string $locale): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }
}

final class UsesParamResolverDeLocaleProvider
{
    public static function getLocale(): string
    {
        return 'de_DE';
    }
}

final class UsesParamResolverDateTestDtoNeedsContextButGotNone extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    #[LocalizedDateTime(locale: '<context')]
    public \DateTimeInterface | string | null $dateContextLocale = null;
}

final class UsesParamResolverDateTestDtoDynamicLocalResolution extends FullDto
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    private string $defaultLocale = 'fr_FR';

    #[LocalizedDateTime]
    public \DateTimeInterface | string | null $date = null;

    public function getLocale(): string
    {
        return $this->defaultLocale;
    }

    public function withLocale(string $locale): static
    {
        $this->defaultLocale = $locale;

        return $this;
    }
}

final class UsesParamResolverThrowsTimezoneProvider extends DateTime
{
    public function __construct()
    {
        parent::__construct();
        $this->constructorArgs = [
            'timezone' => static function (): never {
                throw new \RuntimeException('boom-provider');
            },
        ];
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        $this->resolveParam('timezone', $value);

        return $value;
    }
}

final class UsesParamResolverCallableProviderFailureDto extends FullDto
{
    #[CastTo(UsesParamResolverThrowsTimezoneProvider::class)]
    public ?string $date = null;
}

final class UsesParamResolverPlainHelper
{
    use \Nandan108\DtoToolkit\Traits\UsesParamResolver;

    public function callGetConstructorArg(string $paramName): mixed
    {
        return $this->getConstructorArg($paramName);
    }
}
