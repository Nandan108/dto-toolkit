<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class FromJson extends CastBase
{
    /** @api */
    public function __construct(public readonly bool $asAssoc = true)
    {
        parent::__construct([$asAssoc]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): array | object
    {
        $value = $this->ensureStringable($value);

        try {
            $asAssocFlag = $args[0] ? JSON_OBJECT_AS_ARRAY : 0;

            return json_decode($value, true, 512, JSON_THROW_ON_ERROR | $asAssocFlag);
        } catch (\JsonException $e) {
            throw TransformException::reason(
                value: $value,
                template_suffix: 'json.parsing_failed',
                parameters: ['message' => $e->getMessage()],
                errorCode: 'transform.json',
            );
        }
    }
}
