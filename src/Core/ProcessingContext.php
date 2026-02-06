<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts\ContextStorageInterface;
use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;

final class ProcessingContext
{
    private static ?bool $devMode = null;
    private static ?bool $includeProcessingTraceInErrors = null;

    public static function setDevMode(bool $devMode): void
    {
        self::$devMode = $devMode;
    }

    public static function isDevMode(): bool
    {
        if (null === self::$devMode) {
            self::$devMode = 'dev' === getenv('APP_ENV')
                || '1' === getenv('DEBUG')
                || 'cli' === php_sapi_name() && 'prod' !== getenv('APP_ENV');
        }

        return self::$devMode;
    }

    public static function includeProcessingTraceInErrors(): bool
    {
        return self::$includeProcessingTraceInErrors ?? self::isDevMode();
    }

    public static function setIncludeProcessingTraceInErrors(?bool $include): void
    {
        self::$includeProcessingTraceInErrors = $include;
    }

    private static ?ContextStorageInterface $storage = null;

    /**
     * Set a custom context storage implementation.
     */
    public static function setStorage(ContextStorageInterface $storage): void
    {
        self::$storage = $storage;
    }

    /**
     * Push a processing frame onto the context stack.
     */
    public static function pushFrame(ProcessingFrame $frame): void
    {
        self::storage()->pushFrame($frame);
    }

    /**
     * Pop the current processing frame from the context stack.
     */
    public static function popFrame(): void
    {
        self::storage()->popFrame();
    }

    /**
     * Get the current processing frame.
     */
    public static function current(): ProcessingFrame
    {
        return self::storage()->currentFrame();
    }

    public static function tryCurrent(): ?ProcessingFrame
    {
        return self::storage()->hasFrames()
            ? self::storage()->currentFrame()
            : null;
    }

    /**
     * Get the current DTO from the top frame.
     */
    public static function dto(): BaseDto
    {
        return self::current()->dto;
    }

    /**
     * Get the current error list from the active DTO.
     */
    public static function errorList(): ProcessingErrorList
    {
        return self::dto()->getErrorList();
    }

    /**
     * Get the current error mode from the top frame.
     */
    public static function errorMode(): ErrorMode
    {
        return self::current()->errorMode;
    }

    /**
     * Push a property name onto the current frame's property path stack.
     *
     * @param int|non-empty-string $segment
     */
    public static function pushPropPath(int | string $segment): void
    {
        self::current()->propPathSegments[] = $segment;
    }

    /**
     * Push a node name onto the current frame's property path stack.
     *
     * Node names (prefixed with #) are used to track processing nodes in the
     * property path without affecting the actual path segments.
     *
     * No-op when processing traces are disabled (e.g. in production).
     *
     * @param non-empty-string $name
     *
     * @return bool true if the node was pushed, false if processing traces
     *              are disabled and the node was not added to the path
     */
    public static function pushPropPathNode(string $name): bool
    {
        if (ProcessingContext::includeProcessingTraceInErrors()) {
            self::current()->propPathSegments[] = "#$name";

            return true;
        }

        return false;
    }

    /**
     * Pop the last property path segment from the current frame.
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public static function popPropPath(): int | string | null
    {
        do {
            $segment = array_pop(self::current()->propPathSegments);
        } while (\is_string($segment) && '#' === $segment[0]); // skip node names

        return $segment;
    }

    /**
     * Build and return the current, full property path.
     */
    public static function propPath(): ?string
    {
        $propPaths = [];
        foreach (self::storage()->frames() as $frame) {
            if ($path = $frame->propPath()) {
                $propPaths[] = $path;
            }
        }

        return implode('->', $propPaths) ?: null;
    }

    /**
     * Resolve the storage instance, creating a default one on demand.
     */
    private static function storage(): ContextStorageInterface
    {
        if (null === self::$storage) {
            self::setStorage(new GlobalContextStorage());
        }

        /** @var ContextStorageInterface */
        return self::$storage;
    }

    /**
     * Wrap a processing function within a processing context frame.
     *
     * Note: $errorMode and $errorList cannot be changed within an existing frame for the same DTO.
     *
     * @template T
     *
     * @param \Closure(ProcessingFrame): T $callback
     *
     * @return T
     */
    public static function wrapProcessing(
        BaseDto $dto,
        \Closure $callback,
        ?ErrorMode $errorMode = null,
    ): mixed {
        // is there a current frame for the same DTO?
        if (($frame = self::tryCurrent())?->dto === $dto) {
            /** @var ProcessingFrame $frame */
            if (null !== $errorMode && $errorMode !== $frame->errorMode) {
                throw new InvalidConfigException('Cannot override errorMode within an existing processing frame.');
            }
            if ($dto->getErrorList() !== $frame->errorList) {
                throw new InvalidConfigException('Cannot override errorList within an existing processing frame.');
            }

            // run processing function within existing frame
            return $callback($frame);
        }

        // otherwise, push a new frame
        $context = self::tryCurrent()?->context
            ?? ($dto instanceof HasContextInterface ? $dto->getContext() : []);
        $frame = new ProcessingFrame(
            $dto,
            $dto->getErrorList(),
            $errorMode ?? $dto->getErrorMode(),
            $context,
        );
        self::pushFrame($frame);
        try {
            return $callback($frame);
        } finally {
            ProcessingContext::popFrame();
        }
    }
}
