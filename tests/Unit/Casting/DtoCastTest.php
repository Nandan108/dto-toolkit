<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CreatesFromArrayOrEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Exception\Process\InnerDtoErrorsException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\Exporter;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

final class DtoCastTest extends TestCase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function testConstructorRejectsMissingClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("DTO class 'MissingDto' does not exist.");
        /** @psalm-suppress UndefinedClass, ArgumentTypeCoercion */
        new CastTo\Dto('MissingDto');
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testConstructorRejectsNonCreatesFromArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DTO class');

        /** @psalm-suppress InvalidArgument */
        new CastTo\Dto(NonCreatesFromArrayDto::class);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testConstructorRejectsNonBaseDto(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend BaseDto');

        /** @psalm-suppress InvalidArgument */
        new CastTo\Dto(NonBaseDto::class);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testCastRejectsNonArrayObjectInput(): void
    {
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $caster = new CastTo\Dto(PublicProfileDto::class);

            $this->expectException(TransformException::class);
            $this->expectExceptionMessage('processing.transform.expected');
            $caster->cast('nope', []);
        } finally {
            ProcessingContext::popFrame();
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testCastReturnsDtoFromArrayAndObject(): void
    {
        PropAccess::bootDefaultResolvers();

        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
        ProcessingContext::pushFrame($frame);
        try {
            $caster = new CastTo\Dto(SimpleDto::class);

            $result = $caster->cast(['name' => 'Alice'], [SimpleDto::class]);
            $this->assertInstanceOf(SimpleDto::class, $result);
            $this->assertSame('Alice', $result->name);

            $entity = new class {
                public string $name = 'Bob';
            };
            $result = $caster->cast($entity, [SimpleDto::class]);
            $this->assertInstanceOf(SimpleDto::class, $result);
            $this->assertSame('Bob', $result->name);
        } finally {
            ProcessingContext::popFrame();
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testDtoHasNoErrorsAcceptsCleanDto(): void
    {
        $validator = new Assert\DtoHasNoErrors();
        $dto = new class extends BaseDto {
        };

        $validator->validate($dto);
        $this->assertTrue($dto->getErrorList()->isEmpty());
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testDtoHasNoErrorsRejectsNonDto(): void
    {
        $validator = new Assert\DtoHasNoErrors();
        $dto = new class extends BaseDto {
        };
        $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());

        ProcessingContext::pushFrame($frame);
        try {
            $this->expectException(GuardException::class);
            $this->expectExceptionMessage('processing.guard.nested_dto.errors');
            $validator->validate('not-a-dto');
        } finally {
            ProcessingContext::popFrame();
        }
    }

    public static function getStandardInput(): array
    {
        return [
            'id'             => 123,
            'username'       => 'alice',
            'public_profile' => [
                'name'      => 'Alice Example',
                'birthdate' => '2025-01-02T00:00:00+00:00',
                'bio'       => 'Hello',
                'interests' => [
                    'categories' => '1,2,3',
                    'book'       => '10,11',
                ],
            ],
            'address' => [
                'street'    => '123 Main St',
                'locality'  => 'Springfield',
                'zip'       => '99999',
                'state'     => 'NA',
                'ctry_code' => 'US',
            ],
        ];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testNestedDtoCastingWithGuardPassesOnValidInput(): void
    {
        $input = self::getStandardInput();

        $dto = UserDto::newFromArray($input);

        $this->assertInstanceOf(UserDto::class, $dto);
        $this->assertInstanceOf(PublicProfileDto::class, $dto->public_profile);
        $this->assertInstanceOf(InterestsDto::class, $dto->public_profile->interests);
        $this->assertInstanceOf(AddressDto::class, $dto->address);
        $this->assertSame('alice', $dto->username);
        $this->assertSame([1, 2, 3], $dto->public_profile->interests->categories);
        $this->assertSame('US', $dto->address->ctry_code);
        $this->assertTrue($dto->public_profile->getErrorList()->isEmpty());

        $arrayExport = $dto->exportToArray(recursive: true);
        // Array export should have same values as the original input,
        // except where casting/transformations were applied (dates and comma-split lists in this case).
        $this->assertSame(
            expected: $input['public_profile']['interests']['categories'],
            actual: implode(',', $arrayExport['public_profile']['interests']['categories']),
        );

        $partialArrayExport = $dto->exportToArray(recursive: false);

        // test Exporter directly: from array (recursively converting DTOs) to array
        /** @var array<array-key, mixed> */
        $arrayExport = Exporter::export(source: $partialArrayExport, as: 'array', recursive: true);
        // Array export should have same values as the original input,
        // except where casting/transformations were applied (dates and comma-split lists in this case).
        $this->assertSame(
            expected: $input['public_profile']['interests']['categories'],
            actual: implode(',', $arrayExport['public_profile']['interests']['categories']),
        );

    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testNestedDtoCastingWithGuardThrowsOnInnerErrors(): void
    {
        $input = self::getStandardInput();
        $input['public_profile']['birthdate'] = 'not-a-date';

        try {
            UserDto::newFromArray($input);
            $this->fail('Expected InnerDtoErrorsException to be thrown.');
        } catch (InnerDtoErrorsException $ex) {
            $this->assertInstanceOf(InnerDtoErrorsException::class, $ex);
            $this->assertSame('processing.guard.inner_dto.errors', $ex->getMessageTemplate());
            $this->assertSame(1, $ex->errorList->count());
            $this->assertSame(
                expected: 'public_profile{CastTo\Dto}->birthdate{CastTo\DateTime}',
                actual: $ex->errorList->all()[0]->getPropertyPath(),
            );
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function testNestedDtoCastingWithoutGuardKeepsInnerErrors(): void
    {
        $input = self::getStandardInput();
        $input['public_profile']['birthdate'] = 'not-a-date';

        $dto = UserDtoNoGuard::newFromArray($input);

        $this->assertInstanceOf(PublicProfileDto::class, $dto->public_profile);
        $this->assertSame(1, $dto->public_profile->getErrorList()->count());
    }
}

final class NonCreatesFromArrayDto extends BaseDto
{
}

final class NonBaseDto implements CreatesFromArrayOrEntityInterface
{
    #[\Override]
    public function loadArray(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null, bool $clear = true): static
    {
        return $this;
    }

    #[\Override]
    public function loadArrayLoose(array $input, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null, bool $clear = true): static
    {
        return $this;
    }

    #[\Override]
    public function loadEntity(object $entity, bool $ignoreInaccessibleProps = true, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null, bool $clear = true): static
    {
        return $this;
    }
}

final class SimpleDto extends FullDto
{
    public string $name = '';
}

/** @psalm-suppress PossiblyUnusedProperty */
final class InterestsDto extends FullDto
{
    #[CastTo\Split]
    #[Mod\PerItem, CastTo\Integer]
    public string | array $categories = [];

    #[CastTo\Split]
    #[Mod\PerItem, CastTo\Integer]
    public string | array $book = [];
}

/** @psalm-suppress PossiblyUnusedProperty */
final class PublicProfileDto extends FullDto
{
    protected static ErrorMode $_defaultErrorMode = ErrorMode::CollectFailToNull;

    public string $name = '';

    #[CastTo\DateTime]
    public \DateTimeInterface | string | null $birthdate = null;

    public string $bio = '';

    #[CastTo\Dto(InterestsDto::class)]
    public InterestsDto | array | null $interests = null;
}

/** @psalm-suppress PossiblyUnusedProperty */
final class AddressDto extends FullDto
{
    public string $street = '';
    public string $locality = '';
    public string $zip = '';
    public string $state = '';
    public string $ctry_code = '';
}

/** @psalm-suppress PossiblyUnusedProperty */
final class UserDto extends FullDto
{
    public int $id = 0;
    public string $username = '';

    #[CastTo\Dto(PublicProfileDto::class), Assert\DtoHasNoErrors]
    public PublicProfileDto | array | null $public_profile = null;

    #[CastTo\Dto(AddressDto::class)]
    public AddressDto | array | null $address = null;
}

final class UserDtoNoGuard extends FullDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public int $id = 0;
    /** @psalm-suppress PossiblyUnusedProperty */
    public string $username = '';

    #[CastTo\Dto(PublicProfileDto::class)]
    public PublicProfileDto | array | null $public_profile = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    #[CastTo\Dto(AddressDto::class)]
    public AddressDto | array | null $address = null;
}
