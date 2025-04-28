<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class CastToTest extends TestCase
{
    public function testCastToMethodName(): void
    {
        $dto = new class extends FullDto {
            #[CastTo('localType', [3])]
            public mixed $value = null;

            /** @psalm-suppress MissingParamType */
            public function castToLocalType($value, ...$args): string
            {
                /** @psalm-suppress PossiblyFalseOperand */
                return "$value => localType:".json_encode($args);
            }
        };

        $dto->fill(['value' => 'input']);
        $dto->normalizeInbound();
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
                throw CastingException::castingFailure($this::class, $value, __FUNCTION__, $args, 'Unexpected input');
            }
        };

        $dto->fill(['value1' => 'input1', 'value2' => 'input2']);

        $this->expectException(CastingException::class);
        $this->expectExceptionMessage('Unexpected input');

        $dto->normalizeInbound();
    }
}
