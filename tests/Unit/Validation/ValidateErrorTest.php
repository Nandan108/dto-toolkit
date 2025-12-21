<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\DtoToolkit\Validate;
use PHPUnit\Framework\TestCase;

final class ValidateErrorTest extends TestCase
{
    public function testUnresolvedValidatorThrows(): void
    {
        $this->expectException(NodeProducerResolutionException::class);
        $this->expectExceptionMessage("Unable to resolve processing node producer 'missing'.");

        UnresolvedValidatorDto::fromArray(['name' => 'whatever']);
    }

    public function testNonImplementingClassThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Class 'stdClass' does not implement the ValidatorInterface.");

        NonImplementingValidatorDto::fromArray(['name' => 'anything']);
    }

    public function testValidatorNeedingContainerThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Validator '.NeedsArgsValidator::class.' requires constructor args, but none were provided and no container is available.',
        );

        NeedsContainerValidatorDto::fromArray(['name' => 'z']);
    }
}

final class UnresolvedValidatorDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Validate('missing')]
    public string $name = '';
}

final class NonImplementingValidatorDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Validate(\stdClass::class)]
    public string $name = '';
}

final class NeedsArgsValidator implements ValidatorInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(private string $required)
    {
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $this->required .= 'nop';
        // no-op
    }
}

final class NeedsContainerValidatorDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Validate(NeedsArgsValidator::class)]
    public string $name = '';
}
