<?php

$config = [

    /**
     * Available storages with ID, name and class
     *
     * The class should implement \Storage\StorageInterface
     */
    "storages" => [
        "m" => [
            "name" => "MongoDB",
            "class" => "\\Storage\\Mongo",
            "enabled" => true
        ],
        "f" => [
            "name" => "Filesystem",
            "class" => "\\Storage\\Filesystem",
            "enabled" => false
        ],
        "r" => [
            "name" => "Redis",
            "class" => "\\Storage\\Redis",
            "enabled" => false
        ]
    ],

    /**
     * Current storage id for new data
     *
     * Should be a key in the $storages array
     */
    "storageId" => getenv('STORAGE_ID') ? : "m",

    /**
     * Time in seconds to store data after put or last renew
     */
	'storageTime' => ($env = getenv('STORAGE_TIME')) !== false && $env !== ''
    ? (int) $env
    : 90 * 24 * 60 * 60,


    
    'maxLength' => ($env = getenv('MAX_LENGTH')) !== false && $env !== ''
    ? (int) $env
    : 10 * 1024 * 1024, // 10 MB

    /* Will be cut by length filter */
'maxLines' => ($env = getenv('MAX_LINES')) !== false && $env !== ''
    ? (int) $env
    : 25_000,

];
