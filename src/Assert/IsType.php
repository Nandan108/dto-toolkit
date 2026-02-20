<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validator that checks if a value matches one of the specified types.
 *
 * @template TType = 'bool'|'boolean'|'int'|'integer'|'long'|'float'|'double'
 *                   |'real'|'numeric'|'string'|'class-string'|'scalar'|'array'|'iterable'
 *                   |'countable'|'callable'|'object'|'resource'|'null'
 * *
 * @property array{types: list<string>} $constructorArgs
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsType extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param TType|non-empty-list<TType> $type
     *
     * @api
     */
    public function __construct(string | array $type)
    {
        $types = \is_array($type) ? $type : [$type];
        if ([] === $types) {
            throw new InvalidArgumentException('IsType validator requires at least one type.');
        }

        /** @psalm-suppress DocblockTypeContradiction, InvalidCast */
        /** @var mixed $type */
        foreach ($types as $type) {
            if (!\is_string($type)) {
                throw new InvalidArgumentException('IsType validator expects type names as strings.');
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
                throw new InvalidArgumentException("IsType validator: unknown type '{$type}'.");
            }
        }

        parent::__construct([$types]);
    }

    #[\Override]
    /**
     * @internal
     **/
    public function validate(mixed $value, array $args = []): void
    {
        /** @var list<string> $types */
        $types = $args[0];

        foreach ($types as $type) {
            if ($this->matchesType($value, $type)) {
                return;
            }
        }

        // map type name to snake_cased error message token
        $map = [
            'class-string' => 'class_name',
        ];

        throw GuardException::expected(
            operand: $value,
            expected: array_map(fn (string $t) =>'type.'.($map[$t] ?? $t), $types),
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
