<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

use Nandan108\DtoToolkit\Attribute\ChainModifier\ErrorTemplate;
use Nandan108\DtoToolkit\Contracts\DtoToolkitException;
use Nandan108\DtoToolkit\Contracts\ProcessingExceptionInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;

/**
 * Base exception for processing nodes (casting + validation).
 */
class ProcessingException extends \RuntimeException implements DtoToolkitException, ProcessingExceptionInterface
{
    /** @var non-empty-string */
    public const DOMAIN = 'processing';
    protected static string $defaultErrorCode = self::DOMAIN.'.failure';
    public static int $max_text_length = 100;

    protected string $template;

    /**
     * Parameters should contain only public information to be used in messages:
     * - no internal class names, implementation details, stack traces or DTO structure leakage
     * - no sensitive information = no value contents unless sanitized
     */
    protected array $parameters = [];
    protected array $debug = [];
    protected string | int | null $errorCode = null;
    protected ?string $propertyPath = null;
    protected ?string $throwerNodeName = null;

    /**
     * @param non-empty-string $template_suffix
     */
    public function __construct(
        string $template_suffix,
        array $parameters = [],
        array $debug = [],
        string | int | null $errorCode = null,
        int $httpCode = 422,
    ) {
        $template_suffix = (self::DOMAIN !== static::DOMAIN ? self::DOMAIN.'.' : '').static::DOMAIN.'.'.$template_suffix;
        $this->template = ErrorTemplate::resolve($template_suffix);
        $this->propertyPath = ProcessingContext::propPath();
        $this->parameters = [
            'propertyPath' => $this->propertyPath,
        ] + $parameters;
        $this->debug = $debug;
        $this->errorCode = $errorCode ?? static::$defaultErrorCode;

        parent::__construct($this->template, $httpCode);
    }

    /**
     * Basic reason builder with value info.
     *
     * @param non-empty-string $template_suffix
     */
    final public static function reason(
        mixed $value,
        string $template_suffix,
        array $parameters = [],
        string | int | null $errorCode = null,
        array $debugExtras = [],
    ): self {
        $public = array_merge([
            'type'          => self::normalizeTypeForParams($value),
        ], $parameters);

        $debug = [
            'value'         => self::normalizeValueForDebug($value),
            'type'          => get_debug_type($value),
            'orig_value'    => $value,
        ] + $debugExtras;

        /** @psalm-suppress UnsafeInstantiation */
        return new static(
            template_suffix: $template_suffix,
            parameters: $public,
            debug: $debug,
            errorCode: $errorCode ?? static::$defaultErrorCode,
        );
    }

    /**
     * Basic “validation/transformation failed” builder.
     *
     * @param non-empty-string $template_suffix
     */
    public static function failed(
        string $template_suffix,
        array $parameters = [],
        string | int | null $errorCode = null,
        ?int $httpCode = 422,
        array $debug = [],
    ): self {
        /** @psalm-suppress UnsafeInstantiation */
        return new static(
            template_suffix: $template_suffix,
            parameters: $parameters,
            errorCode: $errorCode ?? static::$defaultErrorCode,
            httpCode: $httpCode ?? 422,
            debug: $debug,
        );
    }

    /**
     * Expected $expected, but got type($operand).
     *
     * @param ?truthy-string $templateSuffix
     */
    public static function expected(
        mixed $operand,
        string | array $expected,
        ?string $templateSuffix = null,
        array $parameters = [],
        array $debug = [],
    ): static {
        /** @var static */
        return static::failed(
            template_suffix: 'expected'.($templateSuffix ? '.'.$templateSuffix : ''),
            errorCode: static::DOMAIN.'.expected',
            parameters: [
                'expected'      => (array) $expected,
                'type'          => self::normalizeTypeForParams($operand),
            ] + $parameters,
            debug: [
                'value'      => self::normalizeValueForDebug($operand),
                'orig_value' => $operand,
            ] + $debug,
        );
    }

    // ProcessingExceptionInterface methods:
    #[\Override]
    public function getMessageTemplate(): string
    {
        return $this->template;
    }

    #[\Override]
    public function getMessageParameters(): array
    {
        return $this->parameters;
    }

    #[\Override]
    public function getErrorCode(): string | int | null
    {
        return $this->errorCode;
    }

    #[\Override]
    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    public function getThrowerNodeName(): ?string
    {
        return $this->throwerNodeName;
    }

    public function setThrowerNodeNameIfMissing(string $throwerNodeName): void
    {
        if (null === $this->throwerNodeName) {
            $this->throwerNodeName = $throwerNodeName;
        }
    }

    /**
     * Get debug info for the exception. This can contain any information that might be useful for
     * debugging but should not contain sensitive information or internal implementation details.
     *
     * @param mixed $key
     */
    public function getDebugInfo(?string $key = null): mixed
    {
        if (null === $key) {
            return $this->debug;
        }

        return $this->debug[$key] ?? [];
    }

    public static function normalizeTypeForParams(mixed $operand): string
    {
        if (is_object($operand)) {
            if ($operand instanceof \Stringable) {
                return 'stringable object';
            }

            if ((new \ReflectionClass($operand))->isAnonymous()) {
                return 'anonymous object';
            }

            return (new \ReflectionClass($operand))->getShortName();
        }

        return get_debug_type($operand);
    }

    /**
     * Serializes $operand and returns a string for the `debug` parameter.
     */
    protected static function normalizeValueForDebug(mixed $operand): string
    {
        // Scalar, empty, or array → try json
        if (null === $operand || is_scalar($operand) || is_array($operand)) {
            $txt = json_encode(
                $operand,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            if (false === $txt) {
                return '[json-encode-error]';
            }

            return self::truncate($txt);
        }

        // Object
        if (is_object($operand)) {
            // Try __toString()
            if (method_exists($operand, '__toString')) {
                $s = (string) $operand;

                return self::truncate($s);
            }

            // Try JsonSerializable
            if ($operand instanceof \JsonSerializable) {
                $txt = json_encode(
                    $operand->jsonSerialize(),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );

                return false !== $txt ? self::truncate($txt) : '[json-error]';
            }

            // Fallback: var_export
            return self::truncate(var_export($operand, true));
        }

        // Fallback
        return '[unrepresentable]';
    }

    protected static function truncate(string $txt): string
    {
        return (strlen($txt) > self::$max_text_length)
            ? substr($txt, 0, self::$max_text_length).'...'
            : $txt;
    }
}
