<?php
// Start session FIRST before any output
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/ArticleController.php';

// DB connection
$db = new Database();
$pdo = $db->getConnection();
$ctrl = new ArticleController($pdo);

// Get article ID from POST request
$articleId = isset($_POST['articleId']) ? intval($_POST['articleId']) : 0;

if ($articleId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid article ID']);
    exit;
}

// Get user identifier (use IP as unique identifier for non-logged-in users)
$userId = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];

try {
    // Check if user already liked this article
    $checkStmt = $pdo->prepare("SELECT id FROM likes WHERE article_id = ? AND user_id = ?");
    $checkStmt->execute([$articleId, $userId]);
    $hasLiked = $checkStmt->fetch() !== false;

    if ($hasLiked) {
        // Remove the like from database
        $deleteStmt = $pdo->prepare("DELETE FROM likes WHERE article_id = ? AND user_id = ?");
        $deleteStmt->execute([$articleId, $userId]);
        
        // Decrement like count
        $ctrl->unlike($articleId);
        $action = 'unliked';
        $newHasLiked = false;
    } else {
        // Add the like to database
        $insertStmt = $pdo->prepare("INSERT INTO likes (article_id, user_id) VALUES (?, ?)");
        $insertStmt->execute([$articleId, $userId]);
        
        // Increment like count
        $ctrl->like($articleId);
        $action = 'liked';
        $newHasLiked = true;
    }

    // Get updated like count
    $likesCount = $ctrl->getLikes($articleId);

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likesCount' => $likesCount,
        'hasLiked' => $newHasLiked
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
exit;
?>
