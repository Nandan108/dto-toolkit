<?php

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\BaseDto;

interface LocaleProviderInterface
{
    public static function getLocale(mixed $value, string $propName, BaseDto $dto): string;
}
