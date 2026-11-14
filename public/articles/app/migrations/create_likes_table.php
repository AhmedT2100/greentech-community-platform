<?php
/**
 * Migration: Create likes table
 * Purpose: Track article likes by user/IP to prevent duplicate likes
 */

require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'likes'");
    if ($stmt->rowCount() > 0) {
        echo "Likes table already exists.\n";
        exit;
    }

    // Create likes table
    $sql = "CREATE TABLE `likes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `article_id` INT NOT NULL,
        `user_id` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_like` (`article_id`, `user_id`),
        FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Likes table created successfully!\n";

} catch (PDOException $e) {
    echo "Error creating likes table: " . $e->getMessage() . "\n";
    exit(1);
}
?>
