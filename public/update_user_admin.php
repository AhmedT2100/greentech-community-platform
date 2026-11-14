<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
$email = app_env('ADMIN_EMAIL');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Set ADMIN_EMAIL.\n"); exit(1); }
$pdo = (new Database())->getConnection();
$stmt = $pdo->prepare('UPDATE utilisateur SET role = ? WHERE email = ?');
$stmt->execute(['admin', $email]);
echo 'Accounts updated: ' . $stmt->rowCount() . PHP_EOL;
