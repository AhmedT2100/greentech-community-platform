<?php
// Deprecated root config.php — keep a small shim that loads the canonical MVC config.
// The real configuration lives in `mvc/Config/config.php`.
include_once __DIR__ . '/mvc/Config/config.php';

// Optional: if you relied on API headers from the old file, define them here as well.
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
}
?>