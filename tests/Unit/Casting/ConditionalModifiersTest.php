<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class ConditionalModifiersTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testApplyNextIf(): void
    {
        // Test the ApplyNextIf modifier
        $dto = new class extends FullDto {
            // Apply Case caster depending on desiredCase
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:pascal'), CastTo\PascalCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:camel'), CastTo\CamelCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:snake'), Mod\Wrap(1), CastTo\SnakeCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:kebab'), CastTo\KebabCase]
            // prefix the value with "separated:" unless the desired case is pascal or camel
            // This also tests passing a json value to the condition method
            #[Mod\SkipNextIf('<dto:desiredCaseIs:["pascal","camel"]'), CastTo\RegexReplace('/^/', 'separated:')]
            public mixed $value = null;

            public function desiredCaseIs(mixed $value, string $prop, string | array $case): bool
            {
                return in_array($this->contextGet('desiredCase'), (array) $case);
            }
        };

        $tests = [
            'pascal' => 'PascalCase',
            'camel'  => 'camelCase',
            'snake'  => 'separated:snake_case',
            'kebab'  => 'separated:kebab-case',
        ];

        foreach ($tests as $case => $expected) {
            $dto->contextSet('desiredCase', $case);

            $dto->loadArray(['value' => "$case case"]);
            $this->assertSame($expected, $dto->value);
        }
    }

    public function testSkipNextIf(): void
    {
        // Test the SkipNextIf modifier
        $dto = new class extends FullDto {
            // Apply PascalCase on odd calls of desiredCaseIs, skip it on even calls
            #[Mod\SkipNextIf('<dto:desiredCaseIs', 2),
                CastTo\PascalCase,
                Mod\FailIf('<context:mustFail'),]
            public mixed $value = null;

            public function desiredCaseIs(): bool
            {
                static $calls = 0;

                return (bool) ($calls++ % 2); // return true on odd calls, false on even calls
            }
        };

        $dto->contextSet('mustFail', false);

        $dto->loadArray(['value' => 'some value']);
        $this->assertSame('SomeValue', $dto->value); // PascalCase applied

        $dto->contextSet('mustFail', true);
        $dto->loadArray(['value' => 'some value']); // failure is skipped

        try {
            $dto->loadArray(['value' => 'some value']);
            $this->fail('Expected exception not thrown');
        } catch (ProcessingException $e) {
            $propPath = $e->getPropertyPath();
            $this->assertSame('value{Mod\SkipNextIf->CastTo\PascalCase->Mod\FailIf}', $propPath); // PascalCase skipped
        }
    }

    public function testApplyNextIfAcceptsClosureConditionProviderOnPhp85AndUp(): void
    {
        if (!self::supportsClosureInAttributeArguments()) {
            $this->markTestSkipped('This runtime does not support closures in attribute arguments.');
        }

        $fqcn = __NAMESPACE__.'\\ConditionalModifiersTest_ClosureAttributeDto';
        if (!class_exists($fqcn, false)) {
            eval(<<<'PHP'
namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;

final class ProvideTrue
{
    public static function returnTrue(): bool { return true; }
}

final class ConditionalModifiersTest_ClosureAttributeDto extends FullDto
{
    #[Mod\ApplyNextIf(condition: static function (mixed $value): bool { return 'apply' === $value; })]
    #[Mod\ApplyNextIf(condition: ProvideTrue::returnTrue(...))]
    #[CastTo\Uppercase]
    public ?string $value = null;
}
PHP
            );
        }

        /** @var class-string<FullDto> $fqcn */
        $dto = $fqcn::newFromArray(['value' => 'apply']);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame('APPLY', $dto->value);

        $dto->unfill()->loadArray(['value' => 'skip']);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame('skip', $dto->value);
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
}
