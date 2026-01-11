<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
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
    public ?string $_valueFilledAtBootTime = null;

    #[Inject]
    private ?DummySluggerForDtoInjection $slugger = null;

    #[\Override]
    public function boot(): void
    {
        $this->_valueFilledAtBootTime = 'hello world';

        // configure injected slugger service
        if ($this->slugger) {
            $this->slugger->separator = '*';
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToSlug(string $value): string
    {
        if (null === $this->slugger) {
            throw new InvalidConfigException('Slugger not injected');
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
        $dto = FooBarDto1::newFromArray(['value' => ' Hello World ']);

        $dto->loadArray(['value' => ' Hello World ']);

        $this->assertEquals('hello*world', $dto->value);

        // testing boot
        $this->assertSame($dto->_valueFilledAtBootTime, 'hello world');

        // instanciate a new Inject attribute instance, just for the heck of having 100% code coverage
        // without this, the #[\Attribute] line of Inject class is marked as not covered instead of n/a.
        (new \ReflectionClass($dto))
            ->getProperty('slugger')
            ->getAttributes(Inject::class)[0]->newInstance();
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
            },
        );

        // create a DTO instance from an array and check that the

        $dto = DtoWithInjectedDummySlugger::newFromArray(['value' => ' Hello World ']);

        $this->assertEquals('hello++world', $dto->value);
    }
}

#[Inject]
final class DtoWithInjectedDummySlugger extends FullDto
{
    #[CastTo('slug')]
    public mixed $value = null;

    // This will be injected by the container
    public function __construct(
        public ?DummySluggerForDtoInjection $_slugger = null,
    ) {
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function castToSlug(mixed $value): ?string
    {
        return $this->_slugger?->slugify($value);
    }

    /** @psalm-suppress PossiblyUnusedReturnValue, PossiblyUnusedMethod */
    protected function getSlugger(): ?DummySluggerForDtoInjection
    {
        return $this->_slugger;
    }
}
