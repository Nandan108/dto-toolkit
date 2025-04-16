<?php

namespace Nandan108\DtoToolkit\Exception;

use RuntimeException;

final class CastingException extends RuntimeException
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $method = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public array $args = [];

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $className = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public mixed $operand = null;

    public static $maxOperandTextLength = 100;

    public static function unresolved(string $methodOrClass): self
    {
        $e         = new self("Caster '{$methodOrClass}' could not be resolved.", 501);
        $e->method = $methodOrClass;
        return $e;
    }

    public static function casterInterfaceNotImplemented(string $className): self
    {
        $e            = new self("Class '{$className}' does not implement the CasterInterface.");
        $e->className = $className;
        return $e;
    }

    public static function castingFailure(string $className, mixed $operand, string $methodName = null, array $args = [], ?string $messageOverride = null): self
    {
        $caster = $className . ($methodName ? '::' . $methodName : '');
        if ($args) {
            try {
                $caster .= '(' . json_encode($args, JSON_THROW_ON_ERROR) . ')';
            } catch (\JsonException $e) {
                $caster .= '(?args?)';
            }
        }
        $operandType = gettype($operand);

        $message = "Caster {$caster} failed to cast $operandType";
        $message = $messageOverride
            ? rtrim($messageOverride, '.') . ': ' . $message
            : $message;

        $getJson  = function ($operand) {
            try {
                return json_encode($operand, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                return '[not serializable: ' . $e->getMessage() . ']';
            }
        };
        $addOpVal = function($txt) use(&$message) {
            if (!$txt) return;
            $message .= ", value: ";
            $message .= strlen($txt) > self::$maxOperandTextLength
                ? substr($txt, 0, self::$maxOperandTextLength) . '...'
                : $txt;
        };

        if (empty($operand) || is_scalar($operand) || is_array($operand)) {
            $addOpVal($getJson($operand) ?? var_export($operand, true));
        } elseif (is_object($operand)) {
            $message .= ", class: " . get_class($operand);
            if (method_exists($operand, '__toString')) {
                $addOpVal($getJson($operand->__toString()));
            } elseif ($operand instanceof \JsonSerializable) {
                $addOpVal($getJson($operand));
            } else {
                $message .= ", value: " . var_export($operand, true);
            }
        }

        $e = new self($message, 422); // 422 Unprocessable Entity

        $e->className = $className;
        $e->method    = $methodName;
        $e->args      = $args;
        $e->operand   = $operand;

        return $e;
    }
}
