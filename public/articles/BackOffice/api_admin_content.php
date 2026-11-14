<?php
session_start();
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/utils/ActivityLogger.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = new Database();
    $pdo = $db->getConnection();
    $logger = new ActivityLogger($pdo);
    $action = $_POST['action'];

    if ($action === 'delete_comment') {
        $commentId = intval($_POST['comment_id'] ?? 0);

        if ($commentId) {
            try {
                // Get comment details for logging
                $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
                $stmt->execute([$commentId]);
                $comment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($comment) {
                    // Delete comment and all its replies (cascading)
                    $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_comment_id = ?")->execute([$commentId, $commentId]);

                    // Log activity
                    $logger->log($_SESSION['admin_id'], 'delete', 'comment', $commentId, "Suppression de commentaire", [
                        'user_name' => $comment['user_name'],
                        'article_id' => $comment['article_id'],
                        'comment_length' => strlen($comment['comment'])
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Commentaire supprimé avec succès']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Commentaire non trouvé']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'ID de commentaire invalide']);
        }
    } elseif ($action === 'create_article') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $image = $_FILES['image'] ?? null;

        if (!$title || !$content) {
            echo json_encode(['success' => false, 'error' => 'Titre et contenu requis']);
            exit;
        }

        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Image requise']);
            exit;
        }

        try {
            // Validate image
            $imageInfo = getimagesize($image['tmp_name']);
            if (!$imageInfo) {
                echo json_encode(['success' => false, 'error' => 'Le fichier doit être une image valide']);
                exit;
            }

            // Check image size (max 5MB)
            if ($image['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'error' => 'La taille de l\'image ne doit pas dépasser 5MB']);
                exit;
            }

            // Generate unique filename
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $filename = 'article_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $filename;

            if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
                echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement de l\'image']);
                exit;
            }

            // Insert article
            $stmt = $pdo->prepare("INSERT INTO articles (title, content, image, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$title, $content, $filename]);

            if ($result) {
                $articleId = $pdo->lastInsertId();

                // Log activity
                $logger->log($_SESSION['admin_id'], 'create', 'article', $articleId, "Création d'article", [
                    'title' => $title,
                    'content_length' => strlen($content),
                    'image' => $filename
                ]);

                echo json_encode(['success' => true, 'message' => 'Article créé avec succès', 'article_id' => $articleId]);
            } else {
                // Clean up uploaded file
                unlink($uploadPath);
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création de l\'article']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
}
?>
