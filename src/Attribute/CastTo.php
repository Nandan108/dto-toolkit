<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class CastTo
{
    public const DTO = 'dto';
    public const ENTITY = 'entity';

    public function __construct(
        public string $method,
        public string $phase = self::DTO
    ) {}
}
