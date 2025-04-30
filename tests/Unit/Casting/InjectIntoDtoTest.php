<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Bridge\ContainerBridge;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Traits\IsInjectable;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

// Dummy service to inject
final class DummySluggerForDtoInjection
{
    public function slugify(string $text, string $separator = '-'): string
    {
        return strtolower(str_replace(' ', $separator, trim($text)));
    }
}

/** @psalm-suppress PropertyNotSetInConstructor */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class DiContainerForTest implements ContainerInterface
{
    #[\Override]
    public function get(string $id)
    {
        if (DummySluggerForDtoInjection::class === $id) {
            return new DummySluggerForDtoInjection();
        }
        throw new \RuntimeException("Unknown type: $id");
    }

    #[\Override]
    public function has(string $id): bool
    {
        return true;
    }
}

final class FooBarDto1 extends FullDto implements Injectable, Bootable
{
    use IsInjectable;

    #[CastTo('slug')]
    public mixed $value = null;
    public ?string $valueFilledAtBootTime = null;

    #[\Override]
    public function boot(): void
    {
        $this->valueFilledAtBootTime = 'hello world';
    }

    #[Inject]
    private ?DummySluggerForDtoInjection $slugger = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToSlug(string $value): string
    {
        if (null === $this->slugger) {
            throw new \RuntimeException('Slugger not injected');
        }

        return $this->slugger->slugify($value, '*');
    }
}

final class InjectIntoDtoTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $container = new DiContainerForTest();
        ContainerBridge::setContainer($container);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        ContainerBridge::setContainer(null);
    }

    public function testInjectionAndBoot(): void
    {
        $dto = FooBarDto1::fromArray(['value' => ' Hello World ']);

        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['value' => ' Hello World ']);

        $this->assertEquals('hello*world', $dto->value);

        // testing boot
        $this->assertSame($dto->valueFilledAtBootTime, 'hello world');
    }
}
