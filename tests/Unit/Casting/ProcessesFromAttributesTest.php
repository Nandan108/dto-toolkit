<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class ProcessesFromAttributesTest extends TestCase
{
    public function testReturnsNormalizedProperties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\IfNull(-1)]
            #[CastTo\Integer]
            public string | int | null $age = null;
        };

        // Case 1: Assert that properties that are not "filled" are not normalized
        $dto->age = '30';
        $dto->processInbound();
        /** @psalm-suppress RedundantCondition */
        $this->assertSame('30', $dto->age);

        // Case 2: Assert that properties that are "filled" are normalized
        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['age' => '30'])->processInbound();
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame(30, $dto->age);
    }

    public function testProcessOutboundAppliesCastsToTaggedProperties(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[Outbound, CastTo\Trimmed]
            public ?string $title = null;

            #[Outbound, CastTo\Str]
            public int | string | null $categoryId = null;

            #[Outbound, CastTo\Str]
            public int | string | null $foo = null;

            #[Outbound, CastTo\Str]
            public int | string | null $emptyString = null;

            #[Outbound, CastTo\Str]
            private int | string | null $privatePropWithSetter = null;

            public function setPrivatePropWithSetter(string $value): void
            {
                $this->privatePropWithSetter = $value;
            }

            public ?string $untouched = null;
        };

        $fooPrinter = new class {
            public function __toString(): string
            {
                return 'foo';
            }
        };

        $normalized = $dto->processOutbound([
            'title'                 => '  Hello  ',
            'categoryId'            => 42,
            'untouched'             => 'value',
            'privatePropWithSetter' => 'val',
            'foo'                   => $fooPrinter,
            'emptyString'           => '',
        ]);

        $this->assertSame($normalized, [
            'title'                 => 'Hello',
            'categoryId'            => '42',
            'untouched'             => 'value',
            'privatePropWithSetter' => 'val',
            'foo'                   => 'foo',
            'emptyString'           => '',
        ]);
    }

    public function testGetCasterThrowsWhenMethodMissing(): void
    {
        $dto = new class extends BaseDto {
            // Note: no castToSomething method defined
        };

        $cast = new CastTo('FakeClassOrMethod');

        $this->expectException(NodeProducerResolutionException::class);

        $cast->getProcessingNode($dto);
    }

    public function testNonRepeatableCasterThrowsInvalidConfigException(): void
    {
        $dto = new class extends FullDto {
            #[NonRepeatableCaster]
            public string $name = '';
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('must be declared with #[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)].');

        /** @psalm-suppress UnusedMethodCall */
        $dto->processInbound();
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class NonRepeatableCaster extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        return $value;
    }
}
