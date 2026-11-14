<?php
session_start();
require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/controllers/ArticleController.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $db = new Database();
    $pdo = $db->getConnection();
    $articleCtrl = new ArticleController($pdo);
    
    // Check if user is logged in
    $currentUserId = $_SESSION['user_id'] ?? null;
    if (!$currentUserId) {
        echo json_encode(['success' => false, 'error' => 'Vous devez être connecté']);
        exit;
    }

    if ($action === 'delete') {
        $articleId = intval($_POST['articleId'] ?? 0);
        
        if (!$articleId) {
            echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
            exit;
        }
        
        // Get article and check ownership
        $article = $articleCtrl->get($articleId);
        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Article non trouvé']);
            exit;
        }
        
        // Check if current user is the article owner
        $articleUserId = $article['user_id'] ?? null;
        if ($articleUserId !== $currentUserId) {
            echo json_encode(['success' => false, 'error' => 'Vous n\'avez pas la permission de supprimer cet article']);
            exit;
        }
        
        try {
            // Delete associated comments and replies first
            $deleteComments = $pdo->prepare("DELETE FROM comments WHERE article_id = ?");
            $deleteComments->execute([$articleId]);
            
            // Delete the article
            $articleCtrl->delete($articleId);
            
            echo json_encode(['success' => true, 'message' => 'Article supprimé avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Action non valide']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
}
?>
