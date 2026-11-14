<?php
require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/models/Comment.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $db = new Database();
    $pdo = $db->getConnection();
    $commentModel = new Comment($pdo);

    if ($action === 'add_reply') {
        $article_id = intval($_POST['article_id'] ?? 0);
        $parent_comment_id = intval($_POST['parent_comment_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $user_name = trim($_POST['user_name'] ?? 'Anonymous');

        if ($article_id && $parent_comment_id && $comment) {
            // Verify parent comment exists
            $parent = $commentModel->getById($parent_comment_id);
            if ($parent && $parent['article_id'] == $article_id) {
                if ($commentModel->create($article_id, $comment, null, $user_name, $parent_comment_id)) {
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
    } elseif ($action === 'get_replies') {
        $comment_id = intval($_GET['comment_id'] ?? 0);
        if ($comment_id) {
            $replies = $commentModel->getReplies($comment_id);
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
