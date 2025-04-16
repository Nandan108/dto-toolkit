<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\BaseDto;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\CasterResolverInterface;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Core\CastBase;
use PHPUnit\Framework\TestCase;

final class CasterInterfaceTest extends TestCase
{
    /** @psalm-suppress MissingOverrideAttribute */
    #[\Override]
    public function tearDown(): void
    {
        CastTo::$customCasterResolver = null;
    }

    public function test_throws_if_class_does_not_exist(): void
    {
        $this->expectException($className = CastingException::class);
        $this->expectExceptionMessage("Caster 'FakeClass' could not be resolved");
        $dto  = new class extends BaseDto {};
        $attr = new CastTo('FakeClass');
        $attr->getCaster($dto);
    }

    public function test_throws_if_class_does_not_implement_interface(): void
    {
        $classNotImplementingCasterInterface = new class {};
        $dto                                 = new class extends BaseDto {};
        $attr                                = new CastTo($className = get_class($classNotImplementingCasterInterface));
        $this->expectException(CastingException::class);
        $this->expectExceptionMessage("Class '{$className}' does not implement the CasterInterface.");
        $attr->getCaster($dto);
    }

    public function test_instantiates_with_constructor_args(): void
    {
        $casterClass = new class ('X') extends CastBase implements CasterInterface {
            public function __construct(public string $prefix) {}
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed
            {
                return $this->prefix . $value;
            }
        };

        $attr   = new CastTo(get_class($casterClass), args: [], constructorArgs: ['X']);
        $dto    = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('Xfoo', $caster('foo'));
    }

    public function test_instantiates_with_no_constructor_args(): void
    {
        $casterClass = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed
            {
                return strtoupper($value);
            }
        };

        $attr   = new CastTo(get_class($casterClass));
        $dto    = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('FOO', $caster('foo'));
    }

    public function test_uses_container_resolver_when_constructor_args_required(): void
    {
        $casterObj = new class ('required') implements CasterInterface {
            public function __construct(public string $prefix) {}
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed
            {
                return $this->prefix . ':' . $value;
            }
        };

        $casterClass = get_class($casterObj);

        $castToSublass = new class ($casterClass) extends CastTo {
            /** @psalm-suppress MoreSpecificReturnType */
            #[\Override]
            public function resolveWithContainer(string $className): CasterInterface
            {
                /** @psalm-suppress InvalidStringClass */
                /** @var CasterInterface */
                return new $className('\SomeNameSpace\MyClass');
            }
        };

        $attr   = new $castToSublass($casterClass);
        $dto    = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('\SomeNameSpace\MyClass:42', $caster('42'));
    }

    public function test_throws_if_constructor_args_are_needed_and_no_resolver_available(): void
    {
        $className = new class ('required') implements CasterInterface {
            public function __construct(string $value) {}
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed
            {
                return $value;
            }
        };

        $casterClass = get_class($className);
        $attr        = new CastTo($casterClass);
        $dto         = new class extends BaseDto {};

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("requires constructor args");
        $attr->getCaster($dto);
    }

    public function test_reuses_cached_instance_and_closure(): void
    {
        $className = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, array $args = []): mixed
            {
                static $calls = 0;
                return ++$calls . ':' . $value;
            }
        };

        $casterClass = get_class($className);
        $dto         = new class extends BaseDto {};

        $attr1 = new CastTo($casterClass, args: ['a']);
        $attr2 = new CastTo($casterClass, args: ['a']);
        $attr3 = new CastTo($casterClass, args: ['b']);

        $caster1 = $attr1->getCaster($dto);
        $caster2 = $attr2->getCaster($dto);
        $caster3 = $attr3->getCaster($dto);

        $this->assertSame('1:foo', $caster1('foo'));
        $this->assertSame('2:bar', $caster2('bar')); // reuses closure
        $this->assertSame('3:baz', $caster3('baz')); // new closure, new args
    }

    public function test_falls_back_to_customCasterResolver_if_class_does_not_exist(): void
    {
        $dto  = new class extends BaseDto {};
        $fakeClassCtorArgs = ['bar'];
        $attr = new CastTo(
            $className = 'FakeClass',
            args: $args = ['foo', 'baz'],
            constructorArgs: $fakeClassCtorArgs
        );

        // Test a custom CasterResolver returning a Closure
        CastTo::$customCasterResolver = new class implements CasterResolverInterface {
            #[\Override]
            public function resolve(string $className, ?array $constructorArgs = []): CasterInterface|\Closure
            {
                /** @psalm-suppress MissingClosureParamType */
                return function (mixed $value, ...$args) use ($className, $constructorArgs) {
                    $ctorArgs   = json_encode($constructorArgs);
                    $castParams = json_encode([$value, $args]);
                    $returnVal = "executing {$className}(...$ctorArgs)->cast(...$castParams)";
                    return $returnVal;
                };
            }
        };

        $casterClosure = $attr->getCaster($dto);
        $castResult = $casterClosure('val', 'foo', 'baz');
        $this->assertSame(
            "executing $className(...[\"bar\"])->cast(...[\"val\",[\"foo\",\"baz\"]])",
            $castResult
        );

        // Test a custom CasterResolver returning a CasterInterface
        CastTo::$customCasterResolver = new class () implements CasterResolverInterface {
            public function __construct() {}

            #[\Override]
            public function resolve(string $className, ?array $constructorArgs = []): CasterInterface|\Closure
            {
                return new class ($className, $constructorArgs) extends CastBase {
                    public function __construct(
                        public string $className,
                        public ?array $constructorArgs = null,
                    ) {}

                    #[\Override]
                    public function cast(mixed $value, array $args = []): mixed
                    {
                        $ctorArgs   = json_encode($this->constructorArgs);
                        $castParams = json_encode([$value, $args]);
                        return "->cast() executing {$this->className}(...$ctorArgs)->cast(...$castParams)";
                    }
                };
            }
        };

        /** @psalm-suppress InvalidReturnType, InvalidNullableReturnType, InvalidReturnStatement, NullableReturnStatement */
        $getMeta = fn(): \stdClass => CastTo::_getCasterMetadata();

        // whipe out memoized caster data
        // FakeClass:a:1:{i:0;s:3:\"bar\";}
        $fakeClassCacheKey = $className.':'.serialize($fakeClassCtorArgs);
        $this->assertObjectHasProperty($fakeClassCacheKey, $getMeta());
        $attr::_clearCasterMetadata();
        $this->assertObjectNotHasProperty($className, $getMeta());

        $casterClosure = $attr->getCaster($dto);
        $this->assertSame(
            "->cast() executing $className(...[\"bar\"])->cast(...[\"val\",[\"foo\",\"baz\"]])",
            $casterClosure('val'),
        );

        /** @var array $casterMeta */
        $allCasters = $getMeta();
        $casterMeta = $allCasters->$fakeClassCacheKey;
        $this->assertArrayHasKey('casters', $casterMeta);
        $attr::_clearCasterMetadata($className);
        $this->assertObjectNotHasProperty($className, $getMeta());

        // get the caster again, which will re-fill the caster cache
        $attr->getCaster($dto);
        // this time, we use getCasterMetadata() with an argument (different code path)
        /** @var array $casterMeta */
        $casterMeta = $attr::_getCasterMetadata($fakeClassCacheKey);
        $this->assertArrayHasKey('casters', $casterMeta);
    }
}
