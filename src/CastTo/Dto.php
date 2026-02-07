<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CreatesFromArrayOrEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Casts nested DTO values from array/object input.
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
            throw new InvalidConfigException("DTO class '{$dtoClass}' does not exist.");
        }
        if (!is_subclass_of($dtoClass, CreatesFromArrayOrEntityInterface::class)) {
            throw new InvalidConfigException("DTO class '{$dtoClass}' must implement CreatesFromArrayOrEntityInterface.");
        }
        if (!is_subclass_of($dtoClass, BaseDto::class)) {
            throw new InvalidConfigException("DTO class '{$dtoClass}' must extend BaseDto.");
        }

        parent::__construct([$dtoClass]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        if (!\is_array($value) && !\is_object($value)) {
            throw TransformException::expected(
                operand: $value,
                expected: 'array|object',
            );
        }

        $dtoClass = $args[0];

        // Note: $dtoClass should be typed as class-string<CreatesFromArrayOrEntityInterface>,
        // but docbloc defined magic methods on interfaces don't seem to be supported by Psalm yet,
        // so we cast to FullDto here.
        /** @var class-string<FullDto> $dtoClass */
        $dto = (\is_array($value))
            ? $dtoClass::newFromArray($value)
            : $dtoClass::newFromEntity($value);

        return $dto;
    }
}
