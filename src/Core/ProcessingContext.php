<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts\ContextStorageInterface;
use Nandan108\DtoToolkit\Contracts\GlobalFrameAwareContextStorageInterface;
use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;

final class ProcessingContext
{
    private static ?bool $devMode = null;
    private static ?bool $includeProcessingTraceInErrors = null;

    /** @api */
    public static function setDevMode(bool $devMode): void
    {
        if (self::$devMode !== $devMode) {
            self::assertNoActiveProcessing('setDevMode');
            self::$devMode = $devMode;
            BaseDto::clearAllCaches();
        }
    }

    /** @api */
    public static function isDevMode(): bool
    {
        if (null === self::$devMode) {
            self::$devMode = 'dev' === getenv('APP_ENV')
                || '1' === getenv('DEBUG')
                || 'cli' === php_sapi_name() && 'prod' !== getenv('APP_ENV');
        }

        return self::$devMode;
    }

    /** @api */
    public static function includeProcessingTraceInErrors(): bool
    {
        return self::$includeProcessingTraceInErrors ?? self::isDevMode();
    }

    /** @api */
    public static function setIncludeProcessingTraceInErrors(?bool $include): void
    {
        if (self::$includeProcessingTraceInErrors !== $include) {
            self::assertNoActiveProcessing('setIncludeProcessingTraceInErrors');
            self::$includeProcessingTraceInErrors = $include;
            BaseDto::clearAllCaches();
        }
    }

    private static ?ContextStorageInterface $storage = null;

    /**
     * Set a custom context storage implementation.
     */
    /** @api */
    public static function setStorage(ContextStorageInterface $storage): void
    {
        self::$storage = $storage;
    }

    /**
     * Push a processing frame onto the context stack.
     *
     * @internal
     */
    public static function pushFrame(ProcessingFrame $frame): void
    {
        self::storage()->pushFrame($frame);
    }

    /**
     * Pop the current processing frame from the context stack.
     *
     * @internal
     */
    public static function popFrame(): void
    {
        self::storage()->popFrame();
    }

    /**
     * Get the current processing frame.
     *
     * @internal
     */
    public static function current(): ProcessingFrame
    {
        return self::storage()->currentFrame();
    }

    /** @internal */
    public static function tryCurrent(): ?ProcessingFrame
    {
        return self::storage()->hasFrames()
            ? self::storage()->currentFrame()
            : null;
    }

    /**
     * Get the current DTO from the top frame.
     */
    /** @api */
    public static function dto(): BaseDto
    {
        return self::current()->dto;
    }

    /**
     * Get the current error list from the active DTO.
     */
    /** @api */
    public static function errorList(): ProcessingErrorList
    {
        return self::dto()->getErrorList();
    }

    /**
     * Get the current error mode from the top frame.
     */
    /** @api */
    public static function errorMode(): ErrorMode
    {
        return self::current()->errorMode;
    }

    /**
     * Push a property name onto the current frame's property path stack.
     *
     * @param int|non-empty-string $segment
     *
     * @internal
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
     * @internal
     */
    public static function pushPropPathNode(string $name): void
    {
        if (ProcessingContext::includeProcessingTraceInErrors()) {
            self::current()->propPathSegments[] = "#$name";
        }
    }

    /** @internal */
    public static function popPropPathNode(): void
    {
        if (ProcessingContext::includeProcessingTraceInErrors()) {
            $frame = self::current();
            $lastKey = array_key_last($frame->propPathSegments);
            null === $lastKey && throw new \LogicException('ProcessingContext: Cannot pop a node name from an empty property path stack.');
            $lastNode = $frame->propPathSegments[$lastKey] ?? null;
            // pop the last node if it's a node (starts with #), otherwise leave it (it will be popped by popPropPath)
            if (\is_string($lastNode) && str_starts_with($lastNode, '#')) {
                array_pop($frame->propPathSegments);
            }
        }
    }

    /**
     * Pop the last property path segment from the current frame.
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     *
     * @internal
     */
    public static function popPropPath(): void
    {
        do {
            $segment = array_pop(self::current()->propPathSegments);
        } while (\is_string($segment) && '#' === $segment[0]); // skip node names
    }

    /**
     * Build and return the current, full property path.
     *
     * @internal
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
            self::setStorage(new DefaultContextStorage());
        }

        /** @var ContextStorageInterface */
        return self::$storage;
    }

    private static function assertNoActiveProcessing(string $method): void
    {
        $storage = self::storage();
        $hasActiveProcessing = $storage instanceof GlobalFrameAwareContextStorageInterface
            ? $storage->hasFramesGlobally()
            : $storage->hasFrames();

        if ($hasActiveProcessing) {
            throw new InvalidConfigException("Cannot call ProcessingContext::$method() while processing is active.");
        }
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
     *
     * @internal
     *
     * @psalm-internal Nandan108\DtoToolkit
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
