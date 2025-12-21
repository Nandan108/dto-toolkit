<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\NodeResolverInterface;
use Nandan108\DtoToolkit\Contracts\ProcessingNodeInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Internal\ProcessingNodeMeta;
use PHPUnit\Framework\TestCase;

final class CasterInterfaceTest extends TestCase
{
    /** @psalm-suppress MissingOverrideAttribute */
    #[\Override]
    public function tearDown(): void
    {
        ProcessingNodeBase::$customNodeResolver = null;
    }

    public function testThrowsIfClassDoesNotExist(): void
    {
        $this->expectException(NodeProducerResolutionException::class);
        $dto = new class extends BaseDto {};
        $attr = new CastTo('FakeClass');
        $attr->getProcessingNode($dto);
    }

    public function testThrowsIfClassDoesNotImplementInterface(): void
    {
        $classNotImplementingCasterInterface = new class {};
        $dto = new class extends BaseDto {};
        $attr = new CastTo($className = get_class($classNotImplementingCasterInterface));
        try {
            $attr->getProcessingNode($dto);
            $this->fail('Expected exception not thrown');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.invalid_interface', $e->getMessageTemplate());
            $this->assertSame($className, $e->getMessageParameters()['className'] ?? null);
        }
    }

    public function testInstantiatesWithConstructorArgs(): void
    {
        $casterClass = new class('X') extends CastBase {
            public function __construct(public string $prefix)
            {
            }

            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return "{$this->prefix}$value";
            }
        };

        $attr = new CastTo(get_class($casterClass), args: [], constructorArgs: ['X']);
        $dto = new class extends BaseDto {};
        $caster = $attr->getProcessingNode($dto);
        $this->assertSame('Xfoo', $caster('foo'));
    }

    public function testInstantiatesWithNoConstructorArgs(): void
    {
        $casterClass = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return strtoupper((string) $value);
            }
        };

        $attr = new CastTo(get_class($casterClass));
        $dto = new class extends BaseDto {};
        $caster = $attr->getProcessingNode($dto);
        $this->assertSame('FOO', $caster('foo'));
    }

    public function testUsesContainerResolverWhenConstructorArgsRequired(): void
    {
        $casterObj = new class('required') implements CasterInterface {
            public function __construct(public string $prefix)
            {
            }

            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return "{$this->prefix}:$value";
            }
        };

        $casterClass = get_class($casterObj);

        $castToSublass = new class($casterClass) extends CastTo {
            /** @psalm-suppress MoreSpecificReturnType */
            #[\Override]
            public function resolveWithContainer(string $className): CasterInterface
            {
                /** @psalm-suppress InvalidStringClass */
                /** @var CasterInterface */
                return new $className('\SomeNameSpace\MyClass');
            }
        };

        $attr = new $castToSublass($casterClass);
        $dto = new class extends BaseDto {};
        $caster = $attr->getProcessingNode($dto);
        $this->assertSame('\SomeNameSpace\MyClass:42', $caster('42'));
    }

    public function testThrowsIfConstructorArgsAreNeededAndNoResolverAvailable(): void
    {
        $className = new class('required') implements CasterInterface {
            public function __construct(string $value)
            {
            }

            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                return $value;
            }
        };

        $casterClass = get_class($className);
        $attr = new CastTo($casterClass);
        $dto = new class extends BaseDto {};

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('requires constructor args');
        $attr->getProcessingNode($dto);
    }

    public function testReusesCachedInstanceAndClosure(): void
    {
        $className = new class implements CasterInterface {
            #[\Override]
            public function cast(mixed $value, array $args): mixed
            {
                static $calls = 0;

                return ++$calls.':'.$value;
            }
        };

        $casterClass = get_class($className);
        $dto = new class extends BaseDto {};

        $attr1 = new CastTo($casterClass, args: ['a']);
        $attr2 = new CastTo($casterClass, args: ['a']);
        $attr3 = new CastTo($casterClass, args: ['b']);

        $caster1 = $attr1->getProcessingNode($dto);
        $caster2 = $attr2->getProcessingNode($dto);
        $caster3 = $attr3->getProcessingNode($dto);

        $this->assertSame('1:foo', $caster1('foo'));
        $this->assertSame('2:bar', $caster2('bar')); // reuses closure
        $this->assertSame('3:baz', $caster3('baz')); // new closure, new args
    }

    public function testFallsBackToCustomCasterResolverIfClassDoesNotExist(): void
    {
        $dto = new class extends BaseDto {};
        $fakeClassCtorArgs = ['bar'];
        $attr = new CastTo(
            $className = 'FakeClass',
            args: $args = ['foo', 'baz'],
            constructorArgs: $fakeClassCtorArgs,
        );

        // Test a custom CasterResolver returning a Closure
        ProcessingNodeBase::$customNodeResolver = new class implements NodeResolverInterface {
            #[\Override]
            public function resolve(string $methodOrClass, array $args = [], array $constructorArgs = []): \Closure
            {
                /** @psalm-suppress MissingClosureParamType */
                return function (mixed $value) use ($methodOrClass, $args, $constructorArgs) {
                    $ctorArgs = json_encode($constructorArgs);
                    $castParams = json_encode([$value, $args]);
                    $returnVal = "executing {$methodOrClass}(...$ctorArgs)->cast(...$castParams)";

                    return $returnVal;
                };
            }
        };

        $casterClosure = $attr->getProcessingNode($dto);
        $castResult = $casterClosure('val');
        $this->assertSame(
            "executing $className(...[\"bar\"])->cast(...[\"val\",[\"foo\",\"baz\"]])",
            $castResult,
        );

        // Test a custom CasterResolver returning a CasterInterface
        ProcessingNodeBase::$customNodeResolver = new class implements NodeResolverInterface {
            public function __construct()
            {
            }

            #[\Override]
            public function resolve(string $methodOrClass, array $args = [], array $constructorArgs = []): CasterInterface | \Closure
            {
                return new class($methodOrClass, $constructorArgs) extends CastBase {
                    public function __construct(
                        public ?string $methodOrClass,
                        public ?array $constructorArgs = null,
                    ) {
                    }

                    #[\Override]
                    public function cast(mixed $value, array $args): mixed
                    {
                        $ctorArgs = json_encode($this->constructorArgs);
                        $castParams = json_encode([$value, $args]);

                        return "->cast() executing {$this->methodOrClass}(...$ctorArgs)->cast(...$castParams)";
                    }
                };
            }
        };

        $getMeta =
        /** @return array<string, array{nodes: array<string, ProcessingNodeMeta>, instance?: ProcessingNodeInterface}> */
        fn (): array => CastTo::_getNodeMetadata();

        // whipe out memoized caster data
        // FakeClass:["bar"]
        /** @psalm-suppress PossiblyFalseOperand */
        $fakeClassCacheKey = $className.':'.json_encode($fakeClassCtorArgs);
        $this->assertArrayHasKey($fakeClassCacheKey, $getMeta());
        $attr::_clearNodeMetadata();
        $this->assertArrayNotHasKey($className, $getMeta());

        $casterClosure = $attr->getProcessingNode($dto);
        $this->assertSame(
            "->cast() executing $className(...[\"bar\"])->cast(...[\"val\",[\"foo\",\"baz\"]])",
            $casterClosure('val'),
        );

        $allCasters = $getMeta();
        $casterMeta = $allCasters[$fakeClassCacheKey];
        $this->assertArrayHasKey('nodes', $casterMeta);
        $attr::_clearNodeMetadata($className);
        $this->assertArrayNotHasKey($className, $getMeta());

        // get the caster again, which will re-fill the caster cache
        $attr->getProcessingNode($dto);

        $casterMeta = $attr::_getNodeMetadata()[$fakeClassCacheKey];
        $this->assertArrayHasKey('nodes', $casterMeta);
    }
}
