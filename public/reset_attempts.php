<?php
// reset_attempts.php
require_once 'config.php';
try {
    $db = (new Database())->getConnection();
    $db->exec("UPDATE utilisateur SET failed_attempts = 0, unlock_code = NULL, unlock_code_expiry = NULL");
    echo "All user attempts reset to 0. You can now test the 3-attempt limit from scratch.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>