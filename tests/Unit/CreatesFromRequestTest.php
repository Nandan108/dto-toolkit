<?php

namespace Tests\Unit;

use Mockery;
use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesOutboundInterface;
use PHPUnit\Framework\TestCase;
use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;
use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Traits\CreatesFromRequest;
use Nandan108\SymfonyDtoToolkit\Traits\NormalizesFromAttributes;
use Symfony\Component\HttpFoundation\Request;

/** @psalm-suppress UnusedClass */
class CreatesFromRequestTest extends TestCase
{
    public function test_instantiates_dto_from_request_object(): void
    {
        // create a request with a POST payload
        $request = new Request(
            query: [ // GET
                'item_id' => $rawItemId = '5',
                'age'     => $rawAge = '30',
            ],
            request: [ // POST
                'email' => $rawEmail = ' john@example.com  ',
                'age'   => '25',
            ],
        );

        $dto = new class extends BaseDto {
            use CreatesFromRequest;
            use NormalizesFromAttributes;

            // imput sources are merged in order, so later sources override earlier ones
            protected array $_inputSources = ['SERVER','COOKIE','FILES','POST', 'GET'];

            public string|int $item_id;
            public string $email;
            public string|int|null $age;
        };

        $dto = $dto::fromRequest($request);

        // still raw
        $this->assertSame($rawEmail, $dto->email);
        // still raw, taken from GET
        $this->assertSame($rawAge, $dto->age);
        $this->assertSame($rawItemId, $dto->item_id);

        // filled
        $this->assertArrayHasKey('email', $dto->filled);
        $this->assertArrayHasKey('age', $dto->filled);
        $this->assertArrayHasKey('item_id', $dto->filled);
    }

    // T
    public function test_throws_if_invalid_input_source(): void {
        $dto = new class extends BaseDto {
            use CreatesFromRequest;
            protected array $_inputSources = [ 'INVALID' ];
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid input source: INVALID');

        $dto::fromRequest(new Request());
    }

    // class must extend BaseDto to use CreatesFromArray
    public function test_throws_exception_if_dto_class_does_not_extend_BaseDto(): void
    {
        $dto = new class {
            use CreatesFromRequest;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must extend BaseDto to use CreatesFromArray');

        $dto::fromRequest(new Request());
    }

    // To support validation groups, the DTO must implement ValidatesInput
    public function test_throws_exception_if_dto_class_does_not_implement_ValidatesInput(): void
    {
        $dto = new class extends BaseDto {
            use CreatesFromRequest;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To support groups, the DTO must implement ValidatesInput.');

        $dto::fromRequest(new Request(), 'Default');
    }
}
