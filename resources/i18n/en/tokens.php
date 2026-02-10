<?php

declare(strict_types=1);

return [

    /* Generic types */
    'type.array'                => 'an array',
    'type.blank'                => 'a blank value',
    'type.bool'                 => 'a boolean',
    'type.class_name'           => 'a valid class name',
    'type.empty_array'          => 'an empty array',
    'type.empty_countable'      => 'an empty countable',
    'type.empty_iterable'       => 'an empty iterable',
    'type.empty_string'         => 'an empty string',
    'type.float'                => 'a float',
    'type.int'                  => 'an integer',
    'type.invalid_string'       => 'an invalid string',
    'type.non_blank'            => 'a non-blank value',
    'type.non_empty_array'      => 'a non-empty array',
    'type.non_empty_countable'  => 'a non-empty countable',
    'type.non_empty_iterable'   => 'a non-empty iterable',
    'type.non_empty_string'     => 'a non-empty string',
    'type.non_empty_stringable' => 'a non-empty stringable value',
    'type.non_null'             => 'a non-null value',
    'type.null'                 => 'a null value',
    'type.numeric_string'       => 'a numeric string',
    'type.numeric'              => 'a number',
    'type.string'               => 'a string',
    'type.stringable'           => 'a stringable value',
    'type.traversable'          => 'a traversable value',
    'type.unknown'              => 'an unknown type',

    /* Object / instance */
    'type.object'            => 'an object',
    'type.instance_of_class' => 'an instance of :class',
    'type.dto'               => 'a DTO',

    /* Enum */
    'type.enum.backing_value' => 'a valid enum backing value',

    /* JSON */
    'type.json.object' => 'a JSON object',

    /* DateTime */
    'type.date_time'           => 'a date and time',
    'type.date_time_interface' => 'a DateTimeInterface instance', // should probably be a InvalidConfigInterface
];
