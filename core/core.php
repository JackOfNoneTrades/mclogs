<?php

use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // path to project root
$dotenv->load();

// Make required env vars mandatory
$required = ['MONGO_URL', 'API_BASE_URL'];
foreach ($required as $var) {
    if (!isset($_ENV[$var]) || $_ENV[$var] === '') {
        fwrite(STDERR, "Error: Required environment variable $var is not set.\n");
        exit(1);
    }
}

// Example: you can now use them
$mongoUrl = $_ENV['MONGO_URL'];
$apiBaseUrl = $_ENV['API_BASE_URL'];

define('CORE_PATH', realpath(dirname(__FILE__)));

require_once(CORE_PATH.'/../vendor/autoload.php');

spl_autoload_register(function ($class) {
    $class = str_replace("\\", "/", $class);
    $classPath = CORE_PATH.'/src/'.$class.'.php';

    if(file_exists($classPath)) {
        include $classPath;
    }
});
