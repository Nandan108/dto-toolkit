<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CreatesFromArrayOrEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Casts nested DTO values from array/object input.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Dto extends CastBase
{
    /**
     * @param class-string<BaseDto&CreatesFromArrayOrEntityInterface> $dtoClass
     */
    public function __construct(string $dtoClass)
    {
        if (!class_exists($dtoClass)) {
            throw new InvalidArgumentException("DTO class '{$dtoClass}' does not exist.");
        }
        if (!is_subclass_of($dtoClass, CreatesFromArrayOrEntityInterface::class)) {
            throw new InvalidArgumentException("DTO class '{$dtoClass}' must implement CreatesFromArrayOrEntityInterface.");
        }
        if (!is_subclass_of($dtoClass, BaseDto::class)) {
            throw new InvalidArgumentException("DTO class '{$dtoClass}' must extend BaseDto.");
        }

        parent::__construct([$dtoClass]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        if (!\is_array($value) && !\is_object($value)) {
            throw TransformException::expected(
                operand: $value,
                expected: ['type.array', 'type.object'],
            );
        }

        /** @var class-string<BaseDto&CreatesFromArrayOrEntityInterface> $dtoClass */
        $dtoClass = $args[0];

        /** @var BaseDto&CreatesFromArrayOrEntityInterface $dto */
        $dto = $dtoClass::new();

        return \is_array($value)
            ? $dto->loadArray($value)
            : $dto->loadEntity($value);
    }
}
