<?php
// .env lives outside the web root. Exported environment variables take precedence.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $values = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if ($values === false) { throw new RuntimeException('Invalid .env configuration'); }
    foreach ($values as $key => $value) {
        if (getenv($key) === false) { putenv($key . '=' . $value); }
    }
}
function app_env($key, $default = '') {
    $value = getenv($key);
    return $value === false ? $default : $value;
}
