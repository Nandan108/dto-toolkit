<?php

namespace Nandan108\DtoToolkit\Attribute;

/**
 * The Outbound attribute is used as a separator, indicating that all following
 * casting attributes will be applied in the outbound phase.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Outbound
{
}
