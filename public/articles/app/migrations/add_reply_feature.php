<?php
/**
 * Migration: Add reply feature to comments
 * Adds parent_comment_id column to support nested comments/replies
 */

require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    // Check if parent_comment_id column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM comments LIKE 'parent_comment_id'");
    
    if ($checkColumn->rowCount() === 0) {
        // Add parent_comment_id column
        $pdo->exec("ALTER TABLE comments ADD COLUMN parent_comment_id INT NULL AFTER article_id");
        
        // Add foreign key constraint
        $pdo->exec("ALTER TABLE comments ADD CONSTRAINT fk_parent_comment 
                   FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE");
        
        echo "✅ Migration completed successfully! Added parent_comment_id column and foreign key.";
    } else {
        echo "ℹ️ parent_comment_id column already exists.";
    }
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage();
}
