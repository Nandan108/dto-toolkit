<?php

namespace Nandan108\SymfonyDtoToolkit\Traits;

use Nandan108\SymfonyDtoToolkit\BaseDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * Trait CreatesFromRequest
 *
 * Enables instantiating a DTO from a Symfony Request object.
 * Internally uses `CreatesFromArray` to hydrate and normalize data.
 */
trait CreatesFromRequest
{
    use CreatesFromArray;

    /**
     * Create a new instance of the DTO from a request
     *
     * @param Request $request
     * @return static
     */
    public static function fromRequest(
        Request $request,
        string|GroupSequence|array|null $groups = null,
        BaseDto $dto = null,
    ): static {
        if (!$dto) {
            $dto = new static();

            if (!$dto instanceof BaseDto) {
                throw new \LogicException(static::class . ' must extend BaseDto to use CreatesFromArray.');
            }
        }

        /** @psalm-suppress NoValue */
        return self::fromArray(
            $dto->getFillableInput($request),
            $groups,
            $dto,
        );
    }

    /**
     * Get the fillable data from the request
     *
     * Can be overriden when special treatment is needed.
     *
     * @param Request $request
     * @return array
     */
    protected function getFillableInput(Request $request): array
    {
        /** @var BaseDto $this
         * @psalm-suppress InaccessibleProperty, InaccessibleMethod
         */
        return array_intersect_key(
            $this->getRequestInput($this->_inputSources, request: $request),
            array_flip($this->getFillable()),
        );
    }

    /**
     * Get the request's input according to the input sources
     *
     * @param Request $request
     * @return array
     */
    protected function getRequestInput(array $sources, Request $request): array
    {
        $input = [];

        foreach ($sources as $source) {
            $bag = match (strtoupper($source)) {
                'GET'     => $request->query,
                'POST'    => $request->request,
                'FILES'   => $request->files,
                'COOKIE'  => $request->cookies,
                'SERVER'  => $request->server,
                default   => throw new \LogicException('Invalid input source: ' . $source),
            };

            if ($bag) {
                $input = array_merge($input, $bag->all());
            }
        }

        return $input;
    }
}
