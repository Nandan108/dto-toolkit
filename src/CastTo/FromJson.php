<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class FromJson extends CastBase
{
    public function __construct(public readonly bool $asAssoc = true)
    {
        parent::__construct([$asAssoc]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): array|object
    {
        $value = $this->throwIfNotStringable($value);

        try {
            $asAssocFlag = $args[0] ? JSON_OBJECT_AS_ARRAY : 0;

            return json_decode($value, true, 512, JSON_THROW_ON_ERROR | $asAssocFlag);
        } catch (\JsonException $e) {
            throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: 'JSON decode error: '.$e->getMessage());
        }
    }
}
