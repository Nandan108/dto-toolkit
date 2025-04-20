<?php

namespace Nandan108\DtoToolkit\Attribute\CastModifier;

use Nandan108\DtoToolkit\Contracts\CastModifierInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;

/**
 * The FailTo attribute is used to catch and handle exceptions
 * thrown by any caster declared earlier in the chain.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class FailTo implements CastModifierInterface
{
    protected \Closure|array|null $_handler = null;

    public function __construct(
        public readonly mixed $fallback = null,
        public readonly string|array|null $handler = null,
        public readonly bool $outbound = false,
    ) {
    }

    #[\Override]
    public function isOutbound(): bool
    {
        return $this->outbound;
    }

    #[\Override]
    public function modify(\ArrayIterator $queue, \Closure $chain, BaseDto $dto): \Closure
    {
        $handler = $this->getHandler($dto);

        return function (mixed $value) use ($chain, $dto, $handler): mixed {
            try {
                // execute upstream chain and return value
                return $chain($value);
            } catch (CastingException $e) {
                return $handler($value, $this->fallback, $e, $dto);
            }
        };
    }

    protected function getHandler(BaseDto $dto): callable
    {
        $fallback = $this->fallback;

        if (null === $this->handler) {
            $handler = fn (): mixed => $fallback;
        } elseif (is_array($this->handler) && is_callable($this->handler)) {
            $handler = $this->handler;
        } elseif (is_string($this->handler) && is_callable([$dto, $this->handler])) {
            $handler = [$dto, $this->handler];
        } else {
            $shortName = (new \ReflectionClass($this))->getShortName();
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $jsonSerializedHandler = json_encode($this->handler) ?: '???';
            throw new \InvalidArgumentException("Invalid $shortName handler: $jsonSerializedHandler, ".'expected DTO method name or valid [class, staticMethod] callable.');
        }

        return $handler;
    }
}
