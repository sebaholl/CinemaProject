<?php
// Loaded at the top of every page: config, helpers, class autoloader, session.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

// Load classes from src/ automatically: new SampleData() loads src/SampleData.php
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
