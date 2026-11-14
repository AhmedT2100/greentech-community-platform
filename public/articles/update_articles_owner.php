<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    die('❌ You must be logged in to update articles.');
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

try {
    // Create DB connection
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Update all articles to belong to the current user
    $stmt = $pdo->prepare("UPDATE articles SET user_id = :user_id, user_name = :user_name");
    $stmt->execute([
        ':user_id' => $userId,
        ':user_name' => $userName
    ]);
    
    $updated = $stmt->rowCount();
    
    echo "✅ Success! Updated $updated articles to belong to: <strong>$userName (ID: $userId)</strong><br><br>";
    echo '<a href="index.php">← Back to Articles</a>';
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
