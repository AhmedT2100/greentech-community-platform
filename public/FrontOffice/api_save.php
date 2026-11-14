<?php
require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/controllers/ArticleController.php';

// Start session
session_start();

// Get user ID (use IP if not logged in)
$userId = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];

// Get article ID from request
$articleId = intval($_POST['articleId'] ?? 0);

if ($articleId === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Article ID requis']);
    exit;
}

// Database connection
$db = new Database();
$pdo = $db->getConnection();

// Check if article is already saved
$checkStmt = $pdo->prepare("SELECT id FROM saved_articles WHERE user_id = ? AND article_id = ?");
$checkStmt->execute([$userId, $articleId]);
$exists = $checkStmt->fetch();

if ($exists) {
    // Remove from saved
    $delStmt = $pdo->prepare("DELETE FROM saved_articles WHERE user_id = ? AND article_id = ?");
    $delStmt->execute([$userId, $articleId]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'action' => 'unsaved',
        'message' => 'Article retiré des favoris'
    ]);
} else {
    // Add to saved
    $insStmt = $pdo->prepare("INSERT INTO saved_articles (user_id, article_id, saved_at) VALUES (?, ?, NOW())");
    $insStmt->execute([$userId, $articleId]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'action' => 'saved',
        'message' => 'Article ajouté aux favoris'
    ]);
}
?>
