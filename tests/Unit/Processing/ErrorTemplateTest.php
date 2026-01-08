<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Processing;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class ErrorTemplateTest extends TestCase
{
    public function testScalarOverride(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\ErrorTemplate('custom.scalar')]
            #[CastTo\Boolean]
            public mixed $flag = null;
        };

        $dto->fill(['flag' => 'not-a-bool']);

        try {
            $dto->processInbound();
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('custom.scalar', $e->getMessageTemplate());
        }
    }

    public function testExactMapOverride(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\ErrorTemplate(['processing.transform.boolean.unable_to_cast' => 'custom.map'])]
            #[CastTo\Boolean]
            public mixed $flag = null;
        };

        $dto->fill(['flag' => 'not-a-bool']);

        try {
            $dto->processInbound();
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('custom.map', $e->getMessageTemplate());
        }
    }

    public function testNestedOverridesFavorInnermost(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\ErrorTemplate('outer.scalar')]
            #[Mod\ErrorTemplate('inner.scalar')]
            #[CastTo\Boolean]
            public mixed $flag = null;
        };

        $dto->fill(['flag' => 'not-a-bool']);

        try {
            $dto->processInbound();
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('inner.scalar', $e->getMessageTemplate());
        }
    }

    public function testMapOverrideBeatsOuterScalarWhenMatching(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\ErrorTemplate('outer.scalar')]
            #[Mod\ErrorTemplate(['processing.transform.boolean.unable_to_cast' => 'inner.map'])]
            #[CastTo\Boolean]
            public mixed $flag = null;
        };

        $dto->fill(['flag' => 'not-a-bool']);

        try {
            $dto->processInbound();
            $this->fail('Expected ProcessingException was not thrown');
        } catch (ProcessingException $e) {
            $this->assertSame('inner.map', $e->getMessageTemplate());
        }
    }

    public function testErrorTemplateFailsIfCountIsLessThanOne(): void
    {
        /** @psalm-suppress InvalidArgument */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[Mod\ErrorTemplate('some.template', -1)]
            #[CastTo\Boolean]
            public mixed $flag = null;
        };
        $dto->fill(['flag' => 'not-a-bool']);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('ErrorTemplate count must be greater than 0');

        $dto->processInbound();
    }
}
