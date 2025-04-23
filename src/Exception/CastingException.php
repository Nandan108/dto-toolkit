<?php

namespace Nandan108\DtoToolkit\Exception;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\CastModifierInterface;

final class CastingException extends \RuntimeException
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $method = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public array $args = [];

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $className = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public mixed $operand = null;

    public static int $maxOperandTextLength = 100;

    public static function unresolved(string $methodOrClass): self
    {
        $e = new self("Caster '{$methodOrClass}' could not be resolved.", 501);
        $e->method = $methodOrClass;

        return $e;
    }

    public static function casterInterfaceNotImplemented(string $className): self
    {
        $e = new self("Class '{$className}' does not implement the CasterInterface.");
        $e->className = $className;

        return $e;
    }

    /** @psalm-suppress PossiblyUnusedMethod, UnusedParam */
    public static function castingFailure(string $className, mixed $operand, ?string $methodName = null, array $args = [], ?string $messageOverride = null): self
    {
        $type = match (true) {
            is_a($className, CasterInterface::class, true)       => 'Caster',
            is_a($className, CastModifierInterface::class, true) => 'Cast modifier',
            default                                              => 'Class',
        };

        $caster = $className;
        if (null !== $methodName && '' !== $methodName) {
            $caster .= '::'.$methodName;
        }

        if ($args) {
            try {
                $caster .= '('.json_encode($args, JSON_THROW_ON_ERROR).')';
            } catch (\JsonException $e) {
                $caster .= '(?args?)';
            }
        }
        $operandType = gettype($operand);

        $message = "$type {$caster} failed to cast $operandType";
        $message = (null === $messageOverride || '' === $messageOverride)
            ? $message
            : rtrim($messageOverride, '.').': '.$message;

        $addOpVal = static function (mixed $operand) use (&$message): void {
            $txt = json_encode($operand, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (false === $txt) {
                $txt = '['.gettype($operand).' not json-serializable: '.json_last_error_msg().']';
            }
            /** @psalm-suppress PossiblyNullArgument */
            $valueMessage = strlen($txt) > self::$maxOperandTextLength
                ? substr($txt, 0, self::$maxOperandTextLength).'...'
                : $txt;
            $message .= ", value: $valueMessage";
        };

        if (empty($operand) || is_scalar($operand) || is_array($operand)) {
            $addOpVal($operand);
        } elseif (is_object($operand)) {
            $message .= ', class: '.get_class($operand);
            if (method_exists($operand, '__toString')) {
                /** @psalm-suppress PossiblyNullArgument */
                $addOpVal($operand->__toString());
            } elseif ($operand instanceof \JsonSerializable) {
                $addOpVal($operand);
            } else {
                $message .= ', value: '.var_export($operand, true);
            }
        }

        $e = new self($message, 422); // 422 Unprocessable Entity

        $e->className = $className;
        $e->method = $methodName;
        $e->args = $args;
        $e->operand = $operand;

        return $e;
    }
}
