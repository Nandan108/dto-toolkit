<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Validation;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Config\NodeProducerResolutionException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class ValidateErrorTest extends TestCase
{
    public function testUnresolvedValidatorThrows(): void
    {
        $this->expectException(NodeProducerResolutionException::class);
        $this->expectExceptionMessage("Unable to resolve processing node producer 'missing'.");

        UnresolvedValidatorDto::newFromArray(['name' => 'whatever']);
    }

    public function testNonImplementingClassThrows(): void
    {
        try {
            NonImplementingValidatorDto::newFromArray(['name' => 'anything']);
            $this->fail('Expected exception not thrown');
        } catch (InvalidConfigException $e) {
            $expected = "Class stdClass does not implement the required interface Nandan108\DtoToolkit\Contracts\ValidatorInterface";
            $this->assertSame($expected, $e->getMessage());
        }
    }

    public function testValidatorNeedingContainerThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Validator '.NeedsArgsValidator::class.' requires constructor args, but none were provided and no container is available.',
        );

        NeedsContainerValidatorDto::newFromArray(['name' => 'z']);
    }

    public function testDtoMethodValidatorErrorIncludesContextInfo(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use CreatesFromArrayOrEntity;
            use ProcessesFromAttributes;

            #[Assert('foo')]
            public int $num = 0;

            public function assertFoo(mixed $value): void
            {
                throw GuardException::failed('assertFoo.failed', errorCode: 'guard.custom_assert_failed');
            }
        };

        try {

            $dto->loadArray(['num' => 42]);
            $this->fail('Expected exception not thrown');
        } catch (GuardException $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('assertFoo.failed', $msg);
            $this->assertSame(
                'num{'.$dto->getProcessingNodeName().'::assertFoo}',
                $e->getPropertyPath(),
            );
        }

    }
}

final class UnresolvedValidatorDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Assert('missing')]
    public string $name = '';
}

final class NonImplementingValidatorDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[Assert(\stdClass::class)]
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
    #[Assert(NeedsArgsValidator::class)]
    public string $name = '';
}
