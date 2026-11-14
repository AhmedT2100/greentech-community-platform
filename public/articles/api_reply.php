<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/CommentController.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $db = new Database();
    $pdo = $db->getConnection();
    $commentCtrl = new CommentController($pdo);

    if ($action === 'add_reply') {
        try {
            $article_id = intval($_POST['article_id'] ?? 0);
            $parent_comment_id = intval($_POST['parent_comment_id'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            $user_name = trim($_POST['user_name'] ?? 'Anonymous');
            $user_id = $_SESSION['user_id'] ?? null;

            if ($article_id && $parent_comment_id && $comment) {
                // Verify parent comment exists
                $parent = $commentCtrl->get($parent_comment_id);
                if ($parent && $parent['article_id'] == $article_id) {
                    // Use the create method with parent_comment_id parameter
                    if ($commentCtrl->create($article_id, $comment, $user_id, $user_name, $parent_comment_id)) {
                        echo json_encode(['success' => true, 'message' => 'Reply added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to add reply']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid parent comment']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            }
        } catch (Exception $e) {
            error_log('Reply error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error adding reply']);
        }
    } elseif ($action === 'get_replies') {
        $comment_id = intval($_GET['comment_id'] ?? 0);
        if ($comment_id) {
            $replies = $commentCtrl->getReplies($comment_id);
            echo json_encode(['success' => true, 'replies' => $replies]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
