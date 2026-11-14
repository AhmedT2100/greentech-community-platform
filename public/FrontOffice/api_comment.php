<?php
session_start();

header('Content-Type: application/json');

// ✅ CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to comment']);
    exit;
}

require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/controllers/CommentController.php';

$db = new Database();
$pdo = $db->getConnection();
$commentCtrl = new CommentController($pdo);

// Get the action and parameters
$action = $_POST['action'] ?? '';
$commentId = isset($_POST['commentId']) ? intval($_POST['commentId']) : 0;
$commentText = $_POST['commentText'] ?? '';
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Anonymous';

// Get comment data
$comment = $commentCtrl->get($commentId);

if (!$comment) {
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit;
}

switch ($action) {
    case 'edit':
        // Check if user owns this comment
        if ($comment['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'You can only edit your own comments']);
            exit;
        }

        if (empty(trim($commentText))) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
            exit;
        }

        if ($commentCtrl->update($commentId, $commentText)) {
            echo json_encode(['success' => true, 'message' => 'Comment updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
        }
        break;

    case 'delete':
        // Check if user owns this comment
        if ($comment['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
            exit;
        }

        if ($commentCtrl->delete($commentId)) {
            echo json_encode(['success' => true, 'message' => 'Comment deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
        break;

    case 'like':
        $clientIp = $_SERVER['REMOTE_ADDR'];
        $likeKey = 'comment_like_' . $commentId . '_' . $clientIp;

        if (!isset($_SESSION['comment_likes'])) {
            $_SESSION['comment_likes'] = [];
        }

        $hasLiked = in_array($likeKey, $_SESSION['comment_likes']);

        if ($hasLiked) {
            $commentCtrl->unlike($commentId);
            $_SESSION['comment_likes'] = array_filter($_SESSION['comment_likes'], function($item) use ($likeKey) {
                return $item !== $likeKey;
            });
            $newHasLiked = false;
        } else {
            $commentCtrl->like($commentId);
            $_SESSION['comment_likes'][] = $likeKey;
            $newHasLiked = true;
        }

        $likesCount = $commentCtrl->getLikes($commentId);

        echo json_encode([
            'success' => true,
            'likesCount' => $likesCount,
            'hasLiked' => $newHasLiked
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

exit;
?>
