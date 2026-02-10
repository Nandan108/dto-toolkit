<?php

declare(strict_types=1);

return [

    /* Extraction */
    'processing.extract.failed' => 'Unable to extract value.',

    /* Generic failures */
    'processing.failure'                 => 'Processing failed.',
    'processing.transform.always_fails'  => 'This processing step always fails.',
    'processing.transform.custom.reason' => 'Transformation failed.',

    /* Guard – generic expectations */
    'processing.guard.expected'             => 'Expected :expected, got :type.',
    'processing.guard.empty'                => 'Expected :expected.',
    'processing.guard.inner_dto.errors'     => 'Inner DTO contains :count error(s).',
    'processing.guard.expected.instance_of' => 'Expected instance of :class, got :type.',

    /* Guard – invalid values */
    'processing.guard.invalid_value'             => 'Invalid value.',
    'processing.guard.invalid_value.bic'         => 'Invalid BIC.',
    'processing.guard.invalid_value.card_scheme' => 'Invalid card scheme.',
    'processing.guard.invalid_value.currency'    => 'Invalid currency.',
    'processing.guard.invalid_value.email'       => 'Invalid email address.',
    'processing.guard.invalid_value.ip'          => 'Invalid IP address.',
    'processing.guard.invalid_value.iban'        => 'Invalid IBAN.',
    'processing.guard.invalid_value.isbn'        => 'Invalid ISBN.',
    'processing.guard.invalid_value.issn'        => 'Invalid ISSN.',
    'processing.guard.invalid_value.json'        => 'Invalid JSON.',
    'processing.guard.invalid_value.luhn'        => 'Invalid checksum.',
    'processing.guard.invalid_value.uuid'        => 'Invalid UUID.',
    'processing.guard.invalid_value.enum.case'   => 'Invalid enum case.',
    'processing.guard.invalid_value.not_in'      => 'Value must be one of :allowed.',

    /* Guard – comparison */
    'processing.guard.invalid_value.compare_to'          => '\':operator\' comparison failed.',
    'processing.guard.invalid_value.compare_to.datetime' => '\':operator\' date comparison failed.',

    /* Guard – containment */
    'processing.guard.contained_in.not_contained'  => 'Value is not contained in the allowed set.',
    'processing.guard.contained_in.type_mismatch'  => 'Expected a traversable value, got :type.',
    'processing.guard.contained_in.non_rewindable' => 'Iterable value is not rewindable.',
    'processing.guard.contains.not_contained'      => 'Expected value to contain the given element.',
    'processing.guard.contains.type_mismatch'      => 'Expected a traversable value, got :type.',
    'processing.guard.contains.non_rewindable'     => 'Iterable value is not rewindable.',

    /* Guard – ranges & length */
    'processing.guard.invalid_value.number.below_min' => 'Number must be greater than :min.',
    'processing.guard.invalid_value.number.above_max' => 'Number must be less than :max.',
    'processing.guard.array_length.below_min'         => 'Array must contain at least :min item(s).',
    'processing.guard.string_length.below_min'        => 'String must be at least :min character(s) long.',
    'processing.guard.string_length.above_max'        => 'String must be at most :max character(s) long.',

    /* Guard – regex */
    'processing.guard.regex.no_match'        => 'Value does not match the required pattern.',
    'processing.guard.regex.match_forbidden' => 'Value matches a forbidden pattern.',

    /* Guard – URL */
    'processing.guard.invalid_url'        => 'Invalid URL.',
    'processing.guard.invalid_url_scheme' => 'Invalid URL scheme. Allowed schemes: :allowed_schemes.',
    'processing.guard.url_missing_scheme' => 'URL is missing a scheme.',
    'processing.guard.url_invalid_host'   => 'Invalid URL host.',

    /* Transform – expectations */
    'processing.transform.expected' => 'Expected :expected, got :type.',

    /* Transform – boolean */
    'processing.transform.boolean.unable_to_cast' => 'Unable to cast value to boolean.',

    /* Transform – base64 */
    'processing.transform.base64.decode_failed' => 'Base64 decoding failed.',

    /* Transform – coalesce */
    'processing.transform.coalesce.no_value' => 'No usable value found.',

    /* Transform – date & time */
    'processing.transform.date.parsing_failed'            => 'Unable to parse date string with format :format.',
    'processing.guard.invalid_value.date.format_mismatch' => 'Date format does not match format :format.',

    /* Transform – enum */
    'processing.transform.enum.invalid_type'                 => 'Invalid value type for enum :enum.',
    'processing.transform.enum.invalid_value'                => 'Invalid enum value for :enum.',
    'processing.guard.expected.backing_value.instance_given' => 'Expected backing value of :enumClass, got an instance.',
    'processing.guard.expected.backing_value.invalid_type'   => 'Expected backing value of :enumClass, got :type.',

    /* Transform – JSON */
    'processing.transform.json.parsing_failed'  => 'JSON parsing failed: :message.',
    'processing.transform.json.encoding_failed' => 'JSON encoding failed.',
    'processing.guard.json.type_mismatch'       => 'JSON type mismatch. Expected :expected, got :type.',

    /* Transform – regex */
    'processing.transform.regex.replace_failed' => 'Regex replacement failed.',
    'processing.transform.regex.split_failed'   => 'Regex split failed.',

    /* Transform – card scheme */
    'processing.transform.card_scheme.no_match' => 'Unable to determine card scheme.',
];
