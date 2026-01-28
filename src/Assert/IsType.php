<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validator that checks if a value matches one of the specified types.
 *
 * @property array{types: list<string>} $constructorArgs
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsType extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @template TType = 'bool'|'boolean'|'int'|'integer'|'long'|'float'|'double'
     *                   |'real'|'numeric'|'string'|'class-string'|'scalar'|'array'|'iterable'
     *                   |'countable'|'callable'|'object'|'resource'|'null'
     *
     * @param TType|list<TType> $type
     */
    public function __construct(string | array $type)
    {
        $types = \is_array($type) ? $type : [$type];
        if ([] === $types) {
            throw new InvalidConfigException('IsType validator requires at least one type.');
        }

        /** @psalm-suppress DocblockTypeContradiction, InvalidCast */
        foreach ($types as $type) {
            if (!\is_string($type)) {
                throw new InvalidConfigException('IsType validator expects type names as strings.');
            }

            if (!in_array(
                $type,
                [
                    'bool', 'boolean',
                    'int', 'integer', 'long',
                    'float', 'double', 'real',
                    'numeric',
                    'string',
                    'class-string',
                    'scalar',
                    'array',
                    'iterable',
                    'countable',
                    'callable',
                    'object',
                    'resource',
                    'null',
                ],
                true,
            )) {
                throw new InvalidConfigException("IsType validator: unknown type '{$type}'.");
            }
        }

        parent::__construct([$types]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $types = $args[0];

        foreach ($types as $type) {
            if ($this->matchesType($value, $type)) {
                return;
            }
        }

        throw GuardException::expected(
            methodOrClass: self::class,
            operand: $value,
            expected: implode('|', $types),
        );
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'bool', 'boolean' => \is_bool($value),
            'int', 'integer', 'long' => \is_int($value),
            'float', 'double', 'real' => \is_float($value),
            'numeric'      => \is_numeric($value),
            'string'       => \is_string($value),
            'class-string' => \is_string($value) && (class_exists($value) || interface_exists($value)),
            'scalar'       => \is_scalar($value),
            'array'        => \is_array($value),
            'iterable'     => \is_iterable($value),
            'countable'    => \is_countable($value),
            'callable'     => \is_callable($value),
            'object'       => \is_object($value),
            'resource'     => \is_resource($value),
            'null'         => null === $value,
            default        => false,
        };
    }
}
