<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use PHPUnit\Framework\TestCase;

final class CastToTest extends TestCase
{
    public function testCastToMethodName(): void
    {
        $dto = new class extends FullDto {
            #[CastTo('localType', args: [3])]
            public mixed $value = null;

            /** @psalm-suppress MissingParamType */
            public function castToLocalType($value, $firstArg): string
            {
                /** @psalm-suppress PossiblyFalseOperand */
                return "$value => localType:".json_encode([$firstArg]);
            }
        };

        $dto->fill(['value' => 'input']);
        $dto->processInbound();
        $this->assertSame('input => localType:[3]', $dto->value);
    }

    public function testCastToMethodNameFailure(): void
    {
        $dto = new class extends FullDto {
            #[CastTo('localType', [3])]
            public mixed $value1 = null;

            // repeated use of same method caster -- should use caster cache
            #[CastTo('localType', [4])]
            public mixed $value2 = null;

            /** @psalm-suppress MissingParamType */
            public function castToLocalType($value, ...$args): string
            {
                throw TransformException::expected(
                    operand: $value,
                    expected: 'Something different',
                );
            }
        };

        $dto->fill(['value1' => 'input1', 'value2' => 'input2']);

        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('processing.transform.expected');

        $dto->processInbound();
    }
}
