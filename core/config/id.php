<?php

$config = [

    /**
     * Available characters for ID generation
     *
     * Don't change! This will break all old IDs.
     */
    "characters" =>  getenv('ID_CHARACTERS') ? : "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890",

    /**
     * ID length (-1 for storage ID)
     */
    'length' => ($env = getenv('ID_LENGTH')) !== false && $env !== ''
    ? (int) $env
    : 6,

];
