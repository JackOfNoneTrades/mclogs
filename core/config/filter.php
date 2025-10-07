<?php

$defaultPreFilters = [
    '\\Filter\\Pre\\Trim',
    '\\Filter\\Pre\\Length',
    '\\Filter\\Pre\\Lines',
    '\\Filter\\Pre\\Ip',
    '\\Filter\\Pre\\Username',
    '\\Filter\\Pre\\AccessToken'
];

// Check if PRE_FILTERS environment variable is defined
$envPreFilters = getenv('PRE_FILTERS');

$config = [
    'pre' => $envPreFilters !== false && $envPreFilters !== null
        ? array_map('trim', preg_split('/\s*,\s*/', $envPreFilters, -1, PREG_SPLIT_NO_EMPTY))
        : $defaultPreFilters,
];
