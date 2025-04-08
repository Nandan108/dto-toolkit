<?php

namespace Tests\Unit\Dto;

use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\SymfonyDtoToolkit\Traits\ValidatesInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Nandan108\SymfonyDtoToolkit\Exception\ValidationException;

class ValidatesInputTest extends TestCase
{
    public function getDto(): BaseDto & ValidatesInputInterface
    {
        return new class extends BaseDto implements ValidatesInputInterface {
            use ValidatesInput;
            #[Assert\NotBlank]
            #[Assert\Length(min: 3, max: 50)]
            public string $name;
            #[Assert\Range(min: 0, max: 120)]
            #[Assert\Type('integer')]
            public int|string $age;
        };
    }
    public function test_valid_dto_passes_validation(): void {

        $dto = $this->getDto()
            ->fill(['name' => 'John Doe', 'age' => 30]);

        try {
            $dto->validate();
            $this->assertTrue(true, 'Validation passed');
        } catch (\Exception $e) {
            $this->fail('Validation failed: ' . $e->getMessage());
        }
    }

    // validate() supports groups
    public function test_validation_support_groups(): void {

        $dto = new class extends BaseDto implements ValidatesInputInterface {
            use ValidatesInput;
            #[Assert\NotBlank(groups: ['create'])]
            #[Assert\Length(min: 3, groups: ['create', 'update'])]
            public ?string $name = null;

            #[Assert\NotBlank]
            #[Assert\Type('integer')]
            public ?int $score = null;
        };

        $dto->fill(['score' => 15]);

        //expect a validation exception since name is missing
        try {
            $dto->validate('create');
        } catch (ValidationException $e) {
            $violations = $e->violations;
            $this->assertCount(1, $violations);
            $this->assertStringContainsString('This value should not be blank.', $violations[0]->getMessage());
        }

        // For the update group, no need to expect an exception (name is not required)
        $dto->validate('update');

        // however, if present, $name must have at least 3 characters
        // $this->expectException(ValidationException::class);
        try {
            $dto->fill(['name' => 'Jo'])->validate('update');
            $this->fail('Validation should have failed and thrown a ValidationException');
        } catch (ValidationException $e) {
            $violations = $e->violations;
            $this->assertCount(1, $violations);
            $this->assertEquals('This value is too short. It should have 3 characters or more.', $violations[0]->getMessage());
        }
    }

    public function test_invalid_dto_throws_a_ValidationException(): void {

        $dto = $this->getDto()
            ->fill(['age' => 'not a number']);

        try {
            $dto->validate();

            $this->fail('Validation should have failed and thrown a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame([], $e->getHeaders());
            $this->assertStringContainsString('Validation failed', $e->getMessage());

            $violations = $e->violations;
            $this->assertCount(3, $violations);
            $this->assertEquals('This value should not be blank.', $violations[0]->getMessage());
            $this->assertEquals('This value should be a valid number.', $violations[1]->getMessage());
            $this->assertEquals('This value should be of type integer.', $violations[2]->getMessage());
        }
    }
}
