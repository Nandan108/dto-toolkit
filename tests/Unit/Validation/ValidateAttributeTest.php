<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class ValidateAttributeTest extends TestCase
{
    public function testDtoMethodValidatorRunsAndThrows(): void
    {
        // positive check
        $check = MethodValidatedDto::newFromArray(['name' => 'not-empty']);
        $this->assertSame('not-empty', $check->name);

        // negative check
        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('empty');

        MethodValidatedDto::newFromArray(['name' => '']);
    }

    public function testClassBasedValidatorRunsAndPasses(): void
    {
        $dto = ClassValidatedDto::newFromArray(['name' => 'ok']);

        $this->assertSame('ok', $dto->name);
    }
}

final class MethodValidatedDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Assert('notEmpty')]
    public ?string $name = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function assertNotEmpty(?string $value): void
    {
        if ('' === $value) {
            throw GuardException::failed('empty');
        }
    }
}

final class NonEmptyValidator implements ValidatorInterface
{
    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        if ('' === $value) {
            throw GuardException::failed('empty');
        }
    }
}

final class ClassValidatedDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    #[Assert(NonEmptyValidator::class)]
    public ?string $name = null;
}
