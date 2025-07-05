<?php

namespace Nandan108\DtoToolkit\Exception;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterChainNodeProducerInterface;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\DtoToolkitException;

final class CastingException extends \RuntimeException implements DtoToolkitException
{
    public ?string $propertyPath;

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $methodName;

    public function __construct(
        string $message,
        public ?string $className = null,
        public mixed $operand = null,
        ?string $methodName = null,
        public array $args = [],
        ?int $code = null,
    ) {
        parent::__construct($message, $code ?? 500);
        $this->propertyPath = CastTo::getPropPath();
        $this->methodName = $methodName;
    }

    public static int $maxOperandTextLength = 100;

    public static function unresolved(string $methodOrClass): self
    {
        $e = new self("Caster '{$methodOrClass}' could not be resolved.", code: 501);
        $e->methodName = $methodOrClass;

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
            is_a($className, CasterInterface::class, true)                  => 'Caster',
            is_a($className, CasterChainNodeProducerInterface::class, true) => 'Chain modifier',
            default                                                         => 'Class',
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

        $e = new self(
            message: $message,
            className: $className,
            operand: $operand,
            methodName: $methodName,
            args: $args,
            code: 422 // Unprocessable Entity
        );
        if ($e->propertyPath ?? '') {
            $e->message = "Prop `{$e->propertyPath}`: ".$e->message;
        }

        return $e;
    }
}
