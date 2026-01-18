<?php

declare(strict_types=1);

namespace Tests\Unit\Processing;

use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBaseNoArgs;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Traits;
use PHPUnit\Framework\TestCase;

/**
 * Dummy caster that always throws a ProcessingException.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class AlwaysFails extends CastBaseNoArgs
{
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        throw new ProcessingException('always fails');
    }
}

final class FailsInboundDto extends BaseDto implements ProcessesInterface
{
    use Traits\CreatesFromArrayOrEntity; // for creating DTOs from arrays
    use Traits\ProcessesFromAttributes; // for casting/transforming properties
    use Traits\ExportsOutbound; // for exporting DTOs to entities

    #[AlwaysFails]
    public ?string $foo = null;
}

final class FailsOutboundDto extends BaseDto implements ProcessesInterface
{
    use Traits\CreatesFromArrayOrEntity; // for creating DTOs from arrays
    use Traits\ProcessesFromAttributes; // for casting/transforming properties
    use Traits\ExportsOutbound; // for exporting DTOs to entities

    /** @psalm-suppress PossiblyUnusedProperty */
    #[CastTo\Uppercase]
    #[Outbound]
    #[AlwaysFails]
    public ?string $foo = null;
}

final class ErrorModeTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        BaseDto::setDefaultErrorMode(ErrorMode::FailFast);
    }

    /* -------------------------------------------------------------------------
     |  INBOUND
     * ------------------------------------------------------------------------*/

    public function testInboundFailFastThrows(): void
    {
        $this->expectException(ProcessingException::class);

        FailsInboundDto::newFromArray(['foo' => 'xxx']);
    }

    public function testInboundCollectFailToInput(): void
    {
        $errors = new ProcessingErrorList();

        $dto = FailsInboundDto::newWithErrorMode(ErrorMode::FailFast)
            ->loadArray(
                ['foo' => 'xxx'],
                errorList: $errors,
                errorMode: ErrorMode::CollectFailToInput,
            );

        self::assertSame('xxx', $dto->foo);
        self::assertCount(1, $errors);
    }

    public function testInboundCollectFailToNull(): void
    {
        $errors = new ProcessingErrorList();

        $dto = FailsInboundDto::newFromArray(
            ['foo' => 'xxx'],
            errorList: $errors,
            errorMode: ErrorMode::CollectFailToNull,
        );

        self::assertNull($dto->foo);
        self::assertCount(1, $errors);
    }

    public function testInboundCollectNone(): void
    {
        $errors = new ProcessingErrorList();

        $dto = FailsInboundDto::newFromArray(
            ['foo' => 'xxx'],
            errorList: $errors,
            errorMode: ErrorMode::CollectNone,
        );

        // Property should be considered "unfilled"
        self::assertNull($dto->foo);
        self::assertFalse(isset($dto->_filled['foo']));
        self::assertCount(1, $errors);
    }

    /* -------------------------------------------------------------------------
     |  OUTBOUND
     * ------------------------------------------------------------------------*/

    public function testOutboundFailFastThrows(): void
    {

        $dto = FailsOutboundDto::newFromArray(['foo' => 'xxx']);

        $this->expectException(ProcessingException::class);

        $dto->toOutboundArray();
    }

    public function testOutboundCollectFailToInput(): void
    {
        $errors = new ProcessingErrorList();
        $dto = FailsOutboundDto::newFromArray(['foo' => 'xxx']);

        $out = $dto->toOutboundArray(
            errorList: $errors,
            errorMode: ErrorMode::CollectFailToInput,
        );

        self::assertSame('XXX', $out['foo']);
        self::assertCount(1, $errors);
    }

    public function testOutboundCollectFailToNull(): void
    {
        $errors = new ProcessingErrorList();
        $dto = FailsOutboundDto::newFromArray(['foo' => 'xxx']);

        self::assertSame($dto->getErrorMode(), ErrorMode::FailFast);

        $out = $dto
            ->withErrorMode(ErrorMode::CollectFailToNull)
            ->toOutboundArray($errors);

        self::assertNull($out['foo']);
        self::assertCount(1, $errors);
    }

    public function testOutboundCollectNone(): void
    {
        $errors = new ProcessingErrorList();
        $dto = FailsOutboundDto::newFromArray(['foo' => 'xxx']);

        $out = $dto->toOutboundArray(
            errorList: $errors,
            errorMode: ErrorMode::CollectNone,
        );

        self::assertArrayNotHasKey('foo', $out);
        self::assertCount(1, $errors);
    }
}
