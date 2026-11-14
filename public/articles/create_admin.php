<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/app/config/Database.php';
$username = app_env('ADMIN_USERNAME');
$email = app_env('ADMIN_EMAIL');
$password = app_env('ADMIN_PASSWORD');
if (!$username || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_USERNAME, ADMIN_EMAIL and ADMIN_PASSWORD (at least 12 characters).\n"); exit(1);
}
$pdo = (new Database())->getConnection();
$stmt = $pdo->prepare('INSERT INTO admins (username, email, password, full_name, is_active) VALUES (?, ?, ?, ?, 1)');
$stmt->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT), app_env('ADMIN_FULL_NAME', 'Administrator')]);
echo "Administrator created.\n";
