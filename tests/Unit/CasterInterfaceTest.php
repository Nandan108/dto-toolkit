<?php

namespace Tests\Unit;

use Nandan108\DtoToolkit\Attribute\CastTo;
use Nandan108\DtoToolkit\BaseDto;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use PHPUnit\Framework\TestCase;

class CasterInterfaceTest extends TestCase
{
    public function test_throws_if_class_does_not_exist(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Missing method 'castToFakeClass'");
        $dto = new class extends BaseDto {};
        $attr = new CastTo('FakeClass');
        $attr->getCaster($dto);
    }

    public function test_throws_if_class_does_not_implement_interface(): void
    {
        $className = new class {};
        $dto = new class extends BaseDto {};
        $attr = new CastTo(get_class($className));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("must implement");
        $attr->getCaster($dto);
    }

    public function test_instantiates_with_constructor_args(): void
    {
        $casterClass = new class('X') implements CasterInterface {
            public function __construct(public string $prefix) {}
            #[\Override]
            public function cast(mixed $value, mixed ...$args): mixed {
                return $this->prefix . $value;
            }
        };

        $attr = new CastTo(get_class($casterClass), args: [], constructorArgs: ['X']);
        $dto = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('Xfoo', $caster('foo'));
    }

    public function test_instantiates_with_no_constructor_args(): void
    {
        $casterClass = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, mixed ...$args): mixed {
                return strtoupper($value);
            }
        };

        $attr = new CastTo(get_class($casterClass));
        $dto = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('FOO', $caster('foo'));
    }

    public function test_uses_container_resolver_when_constructor_args_required(): void
    {
        $casterObj = new class('required') implements CasterInterface {
            public function __construct(public string $prefix) {}
            #[\Override]
            public function cast(mixed $value, mixed ...$args): mixed {
                return $this->prefix . $value;
            }
        };

        $casterClass = get_class($casterObj);

        $castToSublass = new class($casterClass) extends CastTo {
            #[\Override]
            public function resolveFromClassWithContainer(string $className): CasterInterface {
                return new $className('Auto');
            }
        };

        $attr = new $castToSublass($casterClass);
        $dto = new class extends BaseDto {};
        $caster = $attr->getCaster($dto);
        $this->assertSame('Auto42', $caster('42'));
    }

    public function test_throws_if_constructor_args_needed_and_no_resolver(): void
    {
        $className = new class('required') implements CasterInterface {
            public function __construct(string $value) {}
            #[\Override]
            public function cast(mixed $value, mixed ...$args): mixed {
                return $value;
            }
        };

        $casterClass = get_class($className);
        $attr = new CastTo($casterClass);
        $dto = new class extends BaseDto {};

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("requires constructor args");
        $attr->getCaster($dto);
    }

    public function test_reuses_cached_instance_and_closure(): void
    {
        $className = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, mixed ...$args): mixed {
                static $calls = 0;
                return ++$calls . ':' . $value;
            }
        };

        $casterClass = get_class($className);
        $dto = new class extends BaseDto {};

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
}
