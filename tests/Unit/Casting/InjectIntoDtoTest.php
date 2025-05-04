<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\DtoToolkit\Traits\IsInjectable;
use PHPUnit\Framework\TestCase;

// Dummy service to inject
final class DummySluggerForDtoInjection
{
    public function __construct(public ?string $separator = null)
    {
    }

    public function slugify(string $text, string $separator = '-'): string
    {
        $separator = $this->separator ?? $separator;

        return strtolower(str_replace(' ', $separator, trim($text)));
    }
}

final class FooBarDto1 extends FullDto implements Injectable, Bootable
{
    use IsInjectable;

    #[CastTo('slug')]
    public mixed $value = null;
    public ?string $valueFilledAtBootTime = null;

    #[Inject]
    private ?DummySluggerForDtoInjection $slugger = null;

    #[\Override]
    public function boot(): void
    {
        $this->valueFilledAtBootTime = 'hello world';

        // configure injected slugger service
        if ($this->slugger) {
            $this->slugger->separator = '*';
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToSlug(string $value): string
    {
        if (null === $this->slugger) {
            throw new \RuntimeException('Slugger not injected');
        }

        return $this->slugger->slugify($value);
    }
}

final class InjectIntoDtoTest extends TestCase
{
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

    public function testCasterMarkedWithInjectAttrIsInjected(): void
    {
        // Remove container, just rely on ContainerBridge
        ContainerBridge::setContainer(null);

        // make a slugger service with special params and registering it in the container
        ContainerBridge::register(
            abstract: DtoWithInjectedDummySlugger::class,
            concrete: function () {
                return new DtoWithInjectedDummySlugger(new DummySluggerForDtoInjection('++'));
            }
        );

        // create a DTO instance from an array and check that the
        /** @psalm-suppress UndefinedMagicMethod */
        $dto = DtoWithInjectedDummySlugger::fromArray(['value' => ' Hello World ']);

        $this->assertEquals('hello++world', $dto->value);
    }

    public function testCallToProtectedMethodThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $class = DtoWithInjectedDummySlugger::class;
        $this->expectExceptionMessage("Protected method $class::getSlugger() is not reachable from calling context.");

        $dto = new DtoWithInjectedDummySlugger();
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->getSlugger();
    }
}

#[Inject]
final class DtoWithInjectedDummySlugger extends FullDto
{
    #[CastTo('slug')]
    public mixed $value = null;

    // This will be injected by the container
    public function __construct(public ?DummySluggerForDtoInjection $slugger = null)
    {
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToSlug(mixed $value): ?string
    {
        return $this->slugger?->slugify($value);
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    protected function getSlugger(): ?DummySluggerForDtoInjection
    {
        return $this->slugger;
    }
}
