<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/utils/Language.php';
require_once __DIR__ . '/app/controllers/ArticleController.php';
require_once __DIR__ . '/app/controllers/CommentController.php';
require_once __DIR__ . '/app/utils/ContentFilter.php';

// Helper function for relative dates
function getRelativeDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $interval = $now->diff($date);
    
    if ($interval->days == 0) {
        if ($interval->h == 0) {
            return 'maintenant';
        }
        return 'il y a ' . $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
    } elseif ($interval->days < 7) {
        return 'il y a ' . $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
    } else {
        return date('d/m/Y', strtotime($dateString));
    }
}

// Recursive function to display nested replies
function displayNestedReplies($pdo, $parentCommentId, $currentUserId, $sessionCommentLikes, $contentFilter, $depth = 0) {
    try {
        // Get all direct replies to this comment
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE parent_comment_id = ? ORDER BY created_at ASC");
        $stmt->execute([$parentCommentId]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($replies)) {
            return;
        }
        
        // Determine margin based on depth
        $marginLeft = ($depth * 30) . 'px';
        
        echo '<div class="replies-container" style="margin-left: ' . $marginLeft . '">';
        
        foreach ($replies as $reply) {
            $replyUserId = $reply['user_id'] ?? 'guest';
            $replyUserName = $reply['user_name'] ?? 'Anonymous';
            $isReplyOwner = $replyUserId == $currentUserId;
            $replyLikes = $reply['likes'] ?? 0;
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $replyLikeKey = 'comment_like_' . $reply['id'] . '_' . $clientIp;
            $userHasLikedReply = in_array($replyLikeKey, $sessionCommentLikes);
            ?>
            <div class="reply-card" data-reply-id="<?= $reply['id'] ?>">
                <div class="reply-indicator">↳ <span><?= htmlspecialchars($replyUserName) ?></span></div>
                <div class="reply-header">
                    <strong><?= htmlspecialchars($replyUserName) ?></strong>
                    <small><?= getRelativeDate($reply['created_at']) ?></small>
                </div>
                
                <div class="reply-body">
                    <p class="reply-text" id="reply-text-<?= $reply['id'] ?>">
                        <?= nl2br(htmlspecialchars($contentFilter->filterContent($reply['comment']))) ?>
                    </p>
                </div>
                
                <div class="reply-actions">
                    <button class="reply-like-btn" data-reply-id="<?= $reply['id'] ?>" data-liked="<?= $userHasLikedReply ? 'true' : 'false' ?>">
                        👍 Aimer (<span class="reply-likes-count"><?= $replyLikes ?></span>)
                    </button>
                    
                    <button class="reply-reply-btn" data-comment-id="<?= $reply['id'] ?>" data-reply-to="<?= htmlspecialchars($replyUserName) ?>">
                        💬 Répondre
                    </button>
                    
                    <button class="reply-report-btn" data-comment-id="<?= $reply['id'] ?>" title="Signaler cette réponse">
                        🚩 Signaler
                    </button>
                    
                    <?php if ($isReplyOwner): ?>
                        <button class="reply-edit-btn" data-comment-id="<?= $reply['id'] ?>">✏️ Modifier</button>
                        <button class="reply-delete-btn" data-comment-id="<?= $reply['id'] ?>">🗑️ Supprimer</button>
                    <?php endif; ?>
                </div>
                
                <?php if ($isReplyOwner): ?>
                    <div class="reply-edit-form" id="edit-form-<?= $reply['id'] ?>" style="display: none;">
                        <textarea class="edit-reply-text" id="edit-text-<?= $reply['id'] ?>" rows="3"><?= htmlspecialchars($reply['comment']) ?></textarea>
                        <div style="margin-top: 10px; gap: 10px; display: flex;">
                            <button class="reply-save-btn" data-comment-id="<?= $reply['id'] ?>">Enregistrer</button>
                            <button class="reply-cancel-btn" data-comment-id="<?= $reply['id'] ?>" type="button">Annuler</button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Reply form for nested replies -->
                <div class="reply-form-container" id="reply-form-<?= $reply['id'] ?>" style="display: none; margin-top: 15px;">
                    <form class="reply-form-input">
                        <input type="hidden" name="article_id" value="<?= $articleId ?>">
                        <input type="hidden" name="parent_comment_id" value="<?= $reply['id'] ?>">
                        <input type="hidden" name="action" value="add_reply">
                        <textarea name="comment" placeholder="Répondre à <?= htmlspecialchars($replyUserName) ?>..." rows="2" required></textarea>
                        <div style="margin-top: 10px; gap: 10px; display: flex;">
                            <button type="submit" class="btn-submit-reply">Envoyer</button>
                            <button type="button" class="btn-cancel-reply" data-comment-id="<?= $reply['id'] ?>">Annuler</button>
                        </div>
                    </form>
                </div>
                
                <!-- Recursively display nested replies -->
                <?php displayNestedReplies($pdo, $reply['id'], $currentUserId, $sessionCommentLikes, $contentFilter, $depth + 1); ?>
            </div>
            <?php
        }
        
        echo '</div>';
    } catch (Exception $e) {
        echo '<!-- Error loading nested replies -->';
    }
}



// DB connection
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        header('HTTP/1.0 500 Internal Server Error');
        die('Error: Database connection failed');
    }
} catch (Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: Database error - ' . $e->getMessage());
}

// Controllers
try {
    $articleCtrl = new ArticleController($pdo);
    $commentCtrl = new CommentController($pdo);
} catch (Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: Controller initialization failed - ' . $e->getMessage());
}

// Check if article ID is provided
if (!isset($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    die('Error: Article ID manquant');
}

$articleId = intval($_GET['id']);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    // ✅ CHECK IF USER IS LOGGED IN
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login
        header('Location: ../index.html?login=required');
        exit;
    }
    
    $commentText = trim($_POST['comment']);
    
    // Get user info from session
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? 'Anonymous';
    
    if ($commentText !== '') {
        $commentCtrl->create($articleId, $commentText, $userId, $userName);
        header("Location: article.php?id=$articleId");
        exit;
    }
}

// Get the article
try {
    $article = $articleCtrl->get($articleId);
    if (!$article) {
        header('HTTP/1.0 404 Not Found');
        die('Error: Article non trouvé');
    }
} catch (Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: Failed to fetch article - ' . $e->getMessage());
}

// Get current likes
$likesCount = $articleCtrl->getLikes($articleId);

// Check if current user has liked this article
$userId = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
$userHasLiked = false;

try {
    $checkLiked = $pdo->prepare("SELECT id FROM likes WHERE article_id = ? AND user_id = ?");
    $checkLiked->execute([$articleId, $userId]);
    $userHasLiked = $checkLiked->fetch() !== false;
} catch (PDOException $e) {
    // Likes table might not exist yet, set to false
    $userHasLiked = false;
}

// Get current user info
$currentUserId = $_SESSION['user_id'] ?? null;

// Get user ID for saves (use IP if not logged in)
$userId = $currentUserId ?? $_SERVER['REMOTE_ADDR'];

// Check if article is saved by current user (with error handling)
$isSaved = false;
try {
    $checkSaved = $pdo->prepare("SELECT id FROM saved_articles WHERE user_id = ? AND article_id = ?");
    $checkSaved->execute([$userId, $articleId]);
    $isSaved = $checkSaved->fetch() !== false;
} catch (PDOException $e) {
    // Table might not exist yet, set to false
    $isSaved = false;
}

// Get all comments
$comments = $commentCtrl->list($articleId);

// Helper function to calculate reading time
function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $readingTime = ceil($wordCount / 200);
    return $readingTime;
}

// Get related articles (same keywords from title)
$relatedArticles = [];
try {
    $keywords = array_filter(explode(' ', strtolower($article['title'])));
    if (!empty($keywords)) {
        $placeholders = implode(',', array_fill(0, count($keywords), '?'));
        $query = "SELECT a.id, a.title, a.image, a.content, a.likes, a.created_at 
                  FROM articles a 
                  WHERE a.id != ? AND (";
        $params = [$articleId];
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) { // Only keywords with more than 2 chars
                $query .= "LOWER(a.title) LIKE ? OR ";
                $params[] = "%$keyword%";
            }
        }
        
        $query = rtrim($query, ' OR ') . ")";
        $query .= " ORDER BY a.likes DESC LIMIT 3";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $relatedArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $relatedArticles = [];
}

// Initialize content filter for display
$contentFilter = new ContentFilter($pdo);
?>
<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?></title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS File -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="../vendor/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../vendor/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../vendor/lightbox/css/lightbox.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <!-- Article-specific CSS -->
    <style>
        /* Extend banner to cover navigation */
        .banner {
            margin-top: -92px;
            padding-top: 200px !important;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .article-page {
            background: #ffffff;
            padding-top: 150px;
            min-height: 100vh;
        }

        .main-content {
            width: 100%;
            padding: 0;
        }

        .article-header-actions {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px 20px;
        }

        .btn-back {
            display: inline-block;
            background: #1dbf73;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #15a051;
            transform: translateX(-5px);
            color: white;
        }

        .article-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            background: white;
        }

        .article-title-section {
            margin-bottom: 40px;
            position: relative;
        }

        .article-title-section h1 {
            font-size: 52px;
            font-weight: 800;
            color: #1a1a1a;
            line-height: 1.3;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
        }

        .article-edit-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-edit-article,
        .btn-delete-article {
            padding: 10px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-edit-article {
            background: #17a2b8;
            color: white;
        }

        .btn-edit-article:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .btn-delete-article {
            background: #dc3545;
            color: white;
        }

        .btn-delete-article:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .article-image {
            width: 100%;
            max-height: 600px;
            object-fit: cover;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 15px 40px rgba(29, 191, 115, 0.15);
            transition: all 0.3s;
        }

        .article-image:hover {
            transform: scale(1.01);
            box-shadow: 0 20px 50px rgba(29, 191, 115, 0.25);
        }

        .article-page > p {
            font-size: 18px;
            line-height: 1.9;
            color: #555;
            margin-bottom: 25px;
            text-align: justify;
        }

        .article-actions {
            display: flex;
            gap: 20px;
            margin: 40px 0;
            align-items: center;
            flex-wrap: wrap;
        }

        .like-button-heart,
        .save-button-heart {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 28px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
            color: #1a1a1a;
        }

        .like-button-heart:hover,
        .save-button-heart:hover {
            background: #1dbf73;
            border-color: #1dbf73;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(29, 191, 115, 0.4);
        }

        .like-button-heart[data-liked="true"] {
            background: #ff1744;
            border-color: #ff1744;
            color: white;
            box-shadow: 0 8px 20px rgba(255, 23, 68, 0.3);
        }

        .save-button-heart[data-saved="true"] {
            background: #ffc107;
            border-color: #ffc107;
            color: white;
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
        }

        .article-info-share {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            margin: 40px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
        }

        .article-info {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .article-info span {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .share-btn-icon,
        .report-btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .share-btn-icon:hover,
        .report-btn-icon:hover {
            background: #1dbf73;
            border-color: #1dbf73;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(29, 191, 115, 0.4);
        }

        .share-twitter {
            color: #1DA1F2;
        }

        .share-twitter:hover {
            background: #1DA1F2;
            color: white;
        }

        .share-facebook {
            color: #4267B2;
        }

        .share-facebook:hover {
            background: #4267B2;
            color: white;
        }

        /* Comments Section */
        .comments-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin: 50px 0;
        }

        .comments-section h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #1a1a1a;
            border-bottom: 4px solid #1dbf73;
            padding-bottom: 15px;
        }

        .comments-list {
            margin-bottom: 30px;
        }

        /* Related Articles */
        .related-articles {
            margin-top: 60px;
        }

        .related-articles h3 {
            font-size: 32px;
            margin-bottom: 35px;
            color: #1a1a1a;
            border-bottom: 4px solid #1dbf73;
            padding-bottom: 15px;
        }

        .related-articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .related-article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .related-article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(29, 191, 115, 0.25);
        }

        .related-article-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .related-article-card-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .related-article-card h4 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #1a1a1a;
            font-weight: 700;
        }

        .related-article-card p {
            font-size: 15px;
            color: #656565;
            margin-bottom: 15px;
            flex-grow: 1;
            line-height: 1.6;
        }

        .related-article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #999;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* Modal Styles */
        .report-modal,
        .share-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .report-modal.active,
        .share-modal.active {
            display: flex;
        }

        .report-modal-backdrop,
        .share-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
        }

        .report-modal-content,
        .share-modal-content {
            position: relative;
            background: white;
            max-width: 550px;
            width: 90%;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            overflow: hidden;
        }

        .report-modal-header,
        .share-modal-header {
            padding: 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1dbf73 0%, #15a051 100%);
            color: white;
        }

        .report-modal-header h3,
        .share-modal-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .report-modal-close,
        .share-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: white;
            transition: color 0.3s;
        }

        .report-modal-close:hover,
        .share-modal-close:hover {
            color: #f0f0f0;
        }

        .report-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 16px;
        }

        .required {
            color: #dc3545;
        }

        .form-group select,
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input:focus {
            outline: none;
            border-color: #1dbf73;
            box-shadow: 0 0 0 3px rgba(29, 191, 115, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            display: block;
            margin-top: 8px;
            color: #999;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-cancel,
        .btn-submit {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #1a1a1a;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
            transform: translateY(-2px);
        }

        .btn-submit {
            background: #1dbf73;
            color: white;
        }

        .btn-submit:hover {
            background: #15a051;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29, 191, 115, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .article-title-section h1 {
                font-size: 32px;
            }

            .article-info-share {
                flex-direction: column;
                align-items: flex-start;
            }

            .article-info {
                flex-direction: column;
                gap: 15px;
            }

            .article-actions {
                flex-direction: column;
                width: 100%;
            }
        }

        /* Comment Card Styles */
        .comment-thread {
            margin-bottom: 25px;
        }

        .comment-card {
            background: white;
            border-left: 4px solid #1dbf73;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
        }

        .comment-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .comment-header strong {
            color: #1a1a1a;
            font-size: 16px;
        }

        .comment-header small {
            color: #999;
            font-size: 13px;
        }

        .comment-body {
            margin-bottom: 15px;
        }

        .comment-text {
            color: #656565;
            line-height: 1.6;
            margin: 0;
            word-wrap: break-word;
        }

        .comment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .comment-like-btn,
        .comment-reply-btn,
        .comment-report-btn,
        .comment-edit-btn,
        .comment-delete-btn,
        .reply-like-btn,
        .reply-reply-btn,
        .reply-report-btn,
        .reply-edit-btn,
        .reply-delete-btn {
            background: none;
            border: none;
            color: #1dbf73;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            padding: 0;
            transition: all 0.2s;
        }

        .comment-like-btn:hover,
        .comment-reply-btn:hover,
        .comment-report-btn:hover,
        .comment-edit-btn:hover,
        .comment-delete-btn:hover,
        .reply-like-btn:hover,
        .reply-reply-btn:hover,
        .reply-report-btn:hover,
        .reply-edit-btn:hover,
        .reply-delete-btn:hover {
            color: #15a051;
            transform: translateY(-2px);
        }

        .comment-report-btn,
        .reply-report-btn {
            color: #dc3545;
        }

        .comment-report-btn:hover,
        .reply-report-btn:hover {
            color: #c82333;
        }

        .comment-delete-btn,
        .reply-delete-btn {
            color: #dc3545;
        }

        .comment-delete-btn:hover,
        .reply-delete-btn:hover {
            color: #c82333;
        }

        /* Comment Edit Form */
        .comment-edit-form,
        .reply-edit-form {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .edit-comment-text,
        .edit-reply-text {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .edit-comment-text:focus,
        .edit-reply-text:focus {
            outline: none;
            border-color: #1dbf73;
        }

        .comment-save-btn,
        .reply-save-btn {
            background: #1dbf73;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .comment-save-btn:hover,
        .reply-save-btn:hover {
            background: #15a051;
            transform: translateY(-2px);
        }

        .comment-cancel-btn,
        .reply-cancel-btn {
            background: #e0e0e0;
            color: #1a1a1a;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .comment-cancel-btn:hover,
        .reply-cancel-btn:hover {
            background: #d0d0d0;
        }

        /* Reply Form */
        .comment-reply-form,
        .reply-form-container {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .reply-form-input {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .reply-form-input textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .reply-form-input textarea:focus {
            outline: none;
            border-color: #1dbf73;
        }

        .btn-submit-reply,
        .btn-comment-submit {
            background: #1dbf73;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            align-self: flex-start;
        }

        .btn-submit-reply:hover,
        .btn-comment-submit:hover {
            background: #15a051;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 191, 115, 0.3);
        }

        .btn-cancel-reply {
            background: #e0e0e0;
            color: #1a1a1a;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cancel-reply:hover {
            background: #d0d0d0;
        }

        /* Replies Container */
        .replies-container {
            margin-top: 15px;
            border-left: 2px solid #e0e0e0;
            padding-left: 15px;
        }

        .reply-card {
            background: #fafafa;
            border-left: 4px solid #1dbf73;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .reply-indicator {
            color: #999;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .reply-header strong {
            color: #1a1a1a;
            font-size: 15px;
        }

        .reply-header small {
            color: #999;
            font-size: 12px;
        }

        .reply-body {
            margin-bottom: 12px;
        }

        .reply-text {
            color: #656565;
            line-height: 1.6;
            margin: 0;
            word-wrap: break-word;
            font-size: 14px;
        }

        .reply-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            font-size: 13px;
        }

        /* Add Comment Section */
        .add-comment-section {
            margin-top: 40px;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .add-comment-section h3 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        #comment-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.3s;
        }

        #comment-form textarea:focus {
            outline: none;
            border-color: #1dbf73;
        }

        .no-comments {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 16px;
        }

        .comments-list:empty::after {
            content: "Aucun commentaire pour le moment. Soyez le premier à commenter !";
            display: block;
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        /* Ensure proper spacing between comment threads */
        .comment-thread {
            margin-bottom: 20px;
            padding: 15px 0;
        }

        .no-comments {
            color: #999;
            text-align: center;
            padding: 30px;
            font-size: 15px;
        }

            .report-modal-content,
            .share-modal-content {
                margin: 20% auto;
                width: 90%;
            }
        }
    </style>
</head>
<body class="article-page">

<!-- Header Section Start -->
<header class="header">
    <div class="container-fluid">
        <div class="logo pull-left">
            <h1><a href="../index.html">GreenTechHub</a></h1>
        </div>

        <nav id="nav-menu-container">
            <ul class="nav-menu">
                <li><a href="../index.html">Home</a></li>
                <li><a href="../about.html">About</a></li>
                <li><a href="../service.html">Services</a></li>
                <li><a href="../portfolio.html">Portfolio</a></li>
                <li><a href="../events.html">Events</a></li>
                <li><a href="../contact.html">Contact</a></li>
                <li id="loginLink"><a href="#" onclick="openLoginModal(); return false;">Connexion</a></li>
                <li id="signupLink"><a href="#" onclick="openSignupModal(); return false;">Inscription</a></li>
                <li id="adminLink" style="display:none;"><a href="../admin.html">Admin</a></li>
                <li id="userInfo" style="display:none;"></li>
            </ul>
        </nav>
    </div>
</header>
<!-- Header Section End -->

<!-- Banner Section Start-->
<section class="banner">
    <div class="container">
        <div class="col-lg-12">
            <div class="banner-nav">
                <div class="banner-box">
                    <a href="../index.html">Home </a>
                    <a href="index.php">Articles</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End-->

<!-- Hero/Breadcrumb Section -->
<section class="hero-section">
    <div class="hero-content">
    </div>
</section>

<div class="main-content">
    <div class="article-header-actions">
        <a href="index.php" class="btn-back">← Retour aux articles</a>
    </div>

    <div class="article-page">

    <div class="article-title-section">
        <h1><?= htmlspecialchars($article['title']) ?></h1>
        <?php 
            // Check if current user is article owner
            $articleUserId = $article['user_id'] ?? null;
            $currentUserId = $_SESSION['user_id'] ?? null;
            $isArticleOwner = $articleUserId && $currentUserId && $articleUserId == $currentUserId;
        ?>
        <?php if ($isArticleOwner): ?>
            <div class="article-edit-actions">
                <a href="edit_article.php?id=<?= $articleId ?>" class="btn-edit-article" title="Modifier l'article">✏️ Modifier</a>
                <button type="button" class="btn-delete-article" data-article-id="<?= $articleId ?>" title="Supprimer l'article" onclick="deleteArticleHandler(event)">🗑️ Supprimer</button>
            </div>
        <?php endif; ?>
    </div>
    
    <img src="../uploads/<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="article-image">
    <p><?= nl2br(htmlspecialchars($article['content'])) ?></p>

    <!-- Like and Save Buttons Container -->
    <div class="article-actions">
        <!-- Heart Like button -->
        <button type="button" id="likeBtn" class="like-button-heart" data-article-id="<?= $articleId ?>" data-liked="<?= $userHasLiked ? 'true' : 'false' ?>">
            <span class="heart-icon">🤍</span>
            <span class="like-count"><?= $likesCount ?></span>
        </button>

        <!-- Save/Bookmark button -->
        <button type="button" id="saveBtn" class="save-button-heart" data-article-id="<?= $articleId ?>" data-saved="<?= $isSaved ? 'true' : 'false' ?>">
            <span class="save-icon"><?= $isSaved ? '🔖' : '📌' ?></span>
        </button>
    </div>

    <!-- Article Info and Share -->
    <?php 
        $likes = $articleCtrl->getLikes($articleId);
    ?>
    <div class="article-info-share">
        <div class="article-info">
            <span>📅 <?= getRelativeDate($article['created_at']) ?></span>
            <span>❤️ <?= $likes ?> favoris</span>
            <span>⏱️ Temps de lecture: <?= getReadingTime($article['content']) ?> min</span>
        </div>
        <div class="article-actions">
            <!-- Social Share Buttons -->
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
               target="_blank" class="share-btn-icon share-twitter" title="Partager sur Twitter">
                𝕏
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
               target="_blank" class="share-btn-icon share-facebook" title="Partager sur Facebook">
                f
            </a>
            <button class="share-btn-icon" id="copyLinkBtn" title="Copier le lien">
                🔗
            </button>
            <button class="report-btn-icon" id="reportModalBtn" data-articleId="<?= $articleId ?>" title="Signaler ce contenu">
                🚩
            </button>
        </div>
    </div>
    
    <!-- Report Modal -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-backdrop"></div>
        <div class="report-modal-content">
            <div class="report-modal-header">
                <h3>🚩 Signaler ce contenu</h3>
                <button class="report-modal-close" id="closeReportModal">✕</button>
            </div>
            <form id="reportForm" class="report-form">
                <div class="form-group">
                    <label for="reportReason">Raison du signalement: <span class="required">*</span></label>
                    <select id="reportReason" name="reason_type" required>
                        <option value="">-- Sélectionnez une raison --</option>
                        <option value="Contenu offensant">Contenu offensant ou discriminatoire</option>
                        <option value="Spam">Spam ou contenu dupliqué</option>
                        <option value="Contenu inexact">Contenu inexact ou trompeur</option>
                        <option value="Contenu adult">Contenu adulte ou violent</option>
                        <option value="Autre">Autre raison</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reportDetails">Détails supplémentaires (optionnel):</label>
                    <textarea id="reportDetails" name="details" placeholder="Détails supplémentaires..." minlength="5"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="reportEmail">Votre email (optionnel):</label>
                    <input type="email" id="reportEmail" name="email" placeholder="votre@email.com">
                    <small>Nous utiliserons cet email pour vous contacter si nécessaire</small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="cancelReportBtn">Annuler</button>
                    <button type="submit" class="btn-submit">Envoyer le signalement</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Share Modal -->
    <div id="shareModal" class="share-modal">
        <div class="share-modal-backdrop"></div>
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3>📤 Partager cet article</h3>
                <button class="share-modal-close" id="closeShareModal">✕</button>
            </div>
            <div class="share-modal-body">
                <p class="share-subtitle">Choisissez comment partager cette histoire inspirante</p>
                <div class="share-options">
                    <!-- Copy Link -->
                    <button class="share-option" id="copyLinkBtn" data-action="copy">
                        <div class="share-icon">🔗</div>
                        <div class="share-text">
                            <strong>Copier le lien</strong>
                            <small>Copier dans le presse-papiers</small>
                        </div>
                    </button>

                    <!-- Facebook -->
                    <a href="#" class="share-option" id="facebookShareBtn" data-action="facebook">
                        <div class="share-icon">📘</div>
                        <div class="share-text">
                            <strong>Facebook</strong>
                            <small>Partager sur Facebook</small>
                        </div>
                    </a>

                    <!-- Twitter/X -->
                    <a href="#" class="share-option" id="twitterShareBtn" data-action="twitter">
                        <div class="share-icon">𝕏</div>
                        <div class="share-text">
                            <strong>Twitter/X</strong>
                            <small>Partager sur Twitter</small>
                        </div>
                    </a>

                    <!-- Email -->
                    <a href="#" class="share-option" id="emailShareBtn" data-action="email">
                        <div class="share-icon">📧</div>
                        <div class="share-text">
                            <strong>Email</strong>
                            <small>Envoyer par e-mail</small>
                        </div>
                    </a>

                    <!-- WhatsApp -->
                    <a href="#" class="share-option" id="whatsappShareBtn" data-action="whatsapp">
                        <div class="share-icon">💬</div>
                        <div class="share-text">
                            <strong>WhatsApp</strong>
                            <small>Partager sur WhatsApp</small>
                        </div>
                    </a>
                </div>

                <!-- Link Preview -->
                <div class="share-link-preview">
                    <p class="share-link-label">Lien à partager:</p>
                    <div class="share-link-box">
                        <input type="text" id="shareLink" readonly value="<?= 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
                        <button class="copy-btn" onclick="copyShareLink()">📋</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Related Articles Section -->
<?php if (!empty($relatedArticles)): ?>
<div class="related-articles-section">
    <h2>📌 Articles similaires</h2>
    <div class="related-articles-grid">
        <?php foreach ($relatedArticles as $related): 
            $relatedLikes = $related['likes'] ?? 0;
            $relatedReadTime = getReadingTime($related['content']);
        ?>
            <a href="article.php?id=<?= $related['id'] ?>" class="related-article-card">
                <div class="related-article-image">
                    <img src="../uploads/<?= htmlspecialchars($related['image']) ?>" alt="<?= htmlspecialchars($related['title']) ?>">
                </div>
                <div class="related-article-content">
                    <h3><?= htmlspecialchars(substr($related['title'], 0, 50)) ?></h3>
                    <div class="related-article-meta">
                        <span>❤️ <?= $relatedLikes ?></span>
                        <span>⏱️ <?= $relatedReadTime ?> min</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="comments-section">
    <h2>Commentaires</h2>
    
    <?php if (!empty($comments)): ?>
        <div class="comments-list">
            <?php 
            foreach ($comments as $comment):
                // Skip if this is a reply (parent_comment_id is set)
                if (!empty($comment['parent_comment_id'])) {
                    continue;
                }
                
                // Safely get user_id with default fallback
                $commentUserId = intval($comment['user_id'] ?? 0);
                $commentUserName = $comment['user_name'] ?? 'Anonymous';
                $isOwner = $commentUserId > 0 && $commentUserId === intval($currentUserId);
                $commentLikes = $comment['likes'] ?? 0;
                $clientIp = $_SERVER['REMOTE_ADDR'];
                $commentLikeKey = 'comment_like_' . $comment['id'] . '_' . $clientIp;
                $sessionCommentLikes = isset($_SESSION['comment_likes']) ? $_SESSION['comment_likes'] : [];
                $userHasLikedComment = in_array($commentLikeKey, $sessionCommentLikes);
                
                // Get replies for this comment
                $replies = $commentCtrl->getCommentModel()->getReplies($comment['id']);
            ?>
                <div class="comment-thread">
                    <!-- Parent Comment -->
                    <div class="comment-card" data-comment-id="<?= $comment['id'] ?>">
                        <div class="comment-header">
                            <strong><?= htmlspecialchars($commentUserName) ?></strong>
                            <small><?= getRelativeDate($comment['created_at']) ?></small>
                        </div>
                        
                        <div class="comment-body" id="body-<?= $comment['id'] ?>">
                            <p class="comment-text" id="text-<?= $comment['id'] ?>">
                                <?= nl2br(htmlspecialchars($contentFilter->filterContent($comment['comment']))) ?>
                            </p>
                        </div>
                        
                        <div class="comment-actions">
                            <button class="comment-like-btn" data-comment-id="<?= $comment['id'] ?>" data-liked="<?= $userHasLikedComment ? 'true' : 'false' ?>">
                                👍 Aimer (<span class="comment-likes-count"><?= $commentLikes ?></span>)
                            </button>
                            
                            <button class="comment-reply-btn" data-comment-id="<?= $comment['id'] ?>" data-reply-to="<?= htmlspecialchars($commentUserName) ?>">
                                💬 Répondre
                            </button>
                            
                            <button class="comment-report-btn" data-comment-id="<?= $comment['id'] ?>" title="Signaler ce commentaire">
                                🚩 Signaler
                            </button>
                            
                            <?php if ($isOwner): ?>
                                <button class="comment-edit-btn" data-comment-id="<?= $comment['id'] ?>">✏️ Modifier</button>
                                <button class="comment-delete-btn" data-comment-id="<?= $comment['id'] ?>">🗑️ Supprimer</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($isOwner): ?>
                            <div class="comment-edit-form" id="edit-form-<?= $comment['id'] ?>" style="display: none;">
                                <textarea class="edit-comment-text" id="edit-text-<?= $comment['id'] ?>" rows="4"><?= htmlspecialchars($comment['comment']) ?></textarea>
                                <div style="margin-top: 10px; gap: 10px; display: flex;">
                                    <button class="comment-save-btn" data-comment-id="<?= $comment['id'] ?>">Enregistrer</button>
                                    <button class="comment-cancel-btn" data-comment-id="<?= $comment['id'] ?>" type="button">Annuler</button>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Reply Form (hidden by default) -->
                        <div class="comment-reply-form" id="reply-form-<?= $comment['id'] ?>" style="display: none;">
                            <form class="reply-form-input">
                                <input type="hidden" name="article_id" value="<?= $articleId ?>">
                                <input type="hidden" name="parent_comment_id" value="<?= $comment['id'] ?>">
                                <input type="hidden" name="action" value="add_reply">
                                <textarea name="comment" placeholder="Écrivez votre réponse..." rows="3"></textarea>
                                <input type="hidden" name="user_name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Anonymous') ?>">
                                <div style="margin-top: 10px; gap: 10px; display: flex;">
                                    <button type="submit" class="btn-submit-reply">Envoyer</button>
                                    <button type="button" class="btn-cancel-reply" data-comment-id="<?= $comment['id'] ?>">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Nested Replies (recursive) -->
                    <?php displayNestedReplies($pdo, $comment['id'], $currentUserId, $sessionCommentLikes, $contentFilter); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-comments">Aucun commentaire pour le moment.</p>
    <?php endif; ?>

    <!-- Add comment -->
    <div class="add-comment-section">
        <h3>Ajouter un commentaire</h3>
        <?php if (isset($_SESSION['user_id'])): ?>
            <form action="" method="POST" id="comment-form" novalidate>
                <textarea name="comment" placeholder="Écrivez votre commentaire..." rows="4"></textarea>
                <button type="submit" class="btn-comment-submit">Publier</button>
            </form>
        <?php else: ?>
            <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                <p style="color: #656565; margin-bottom: 15px;">🔒 Vous devez être connecté pour commenter</p>
                <button class="btn-comment-submit" onclick="openLoginModal(); return false;" style="cursor: pointer;">
                    Se connecter
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>
</div>

<script>
// Report Modal Functions
function openReportModal() {
    const reportModal = document.getElementById('reportModal');
    reportModal.classList.add('active');
}

function closeReportModal() {
    const reportModal = document.getElementById('reportModal');
    reportModal.classList.remove('active');
}

// Report button listener
document.getElementById('reportModalBtn')?.addEventListener('click', openReportModal);
document.getElementById('closeReportModal')?.addEventListener('click', closeReportModal);
document.getElementById('cancelReportBtn')?.addEventListener('click', closeReportModal);

// Close report modal when clicking on backdrop
document.getElementById('reportModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReportModal();
    }
});
// Handle report form submission
document.getElementById('reportForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const reasonType = document.getElementById('reportReason').value;
    const details = document.getElementById('reportDetails').value;
    const email = document.getElementById('reportEmail').value;
    
    // Build reason with details
    let reason = reasonType;
    if (details) {
        reason = `${reasonType}: ${details}`;
    }
    
    // Send report
    fetch('api_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            report_type: 'article',
            entity_id: <?= $articleId ?>,
            reason: reason,
            email: email || null
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              showSuccess('Merci! Votre signalement a été envoyé.');
              closeReportModal();
              document.getElementById('reportForm').reset();
          } else {
              showError(data.error || 'Erreur lors de l\'envoi du signalement');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          showError('Erreur lors de l\'envoi du signalement');
      });
});

// Share Modal Functions
function openShareModal() {
    const shareModal = document.getElementById('shareModal');
    shareModal.classList.add('active');
}

function closeShareModal() {
    const shareModal = document.getElementById('shareModal');
    shareModal.classList.remove('active');
}

document.getElementById('closeShareModal')?.addEventListener('click', closeShareModal);

// Close share modal when clicking on backdrop
document.getElementById('shareModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});

// Copy link to clipboard
const copyLinkBtns = document.querySelectorAll('#copyLinkBtn');
copyLinkBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<div class="share-icon">✅</div>';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        }).catch(() => {
            alert('Impossible de copier le lien');
        });
    });
});

// Copy share link
function copyShareLink() {
    const shareLink = document.getElementById('shareLink');
    shareLink.select();
    document.execCommand('copy');
    
    const btn = event.target;
    btn.textContent = '✅';
    setTimeout(() => {
        btn.textContent = '📋';
    }, 2000);
}

// Share social media
document.getElementById('facebookShareBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const url = window.location.href;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, 'facebook-share', 'width=600,height=400');
});

document.getElementById('twitterShareBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const url = window.location.href;
    const text = '<?= htmlspecialchars($article['title']) ?>';
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, 'twitter-share', 'width=600,height=400');
});

document.getElementById('emailShareBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const url = window.location.href;
    const title = '<?= htmlspecialchars($article['title']) ?>';
    window.location.href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`;
});

document.getElementById('whatsappShareBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    const url = window.location.href;
    const text = '<?= htmlspecialchars($article['title']) ?>';
    window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
});
</script>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Connexion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Accédez à votre tableau de bord GreenTechHub</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="loginEmail" placeholder="votre@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="loginPassword" placeholder="Votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-log-in"></i> Se connecter
                    </button>
                </form>
                <p class="text-center mb-0">Pas encore de compte ? <a href="#" onclick="switchToSignup()">Créer un compte</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Signup Modal -->
<div class="modal fade" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Créer un compte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Rejoignez la communauté GreenTechHub</p>
                <form id="signupForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="firstName" placeholder="Votre prénom" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="lastName" placeholder="Votre nom" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="signupEmail" placeholder="votre@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="signupPassword" placeholder="Au moins 8 caractères" required>
                        </div>
                        <div style="margin-top: 8px; height: 5px; background: #e0e0e0; border-radius: 3px;">
                            <div id="passwordStrength" style="height: 100%; width: 0%; border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirmez votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="acceptTerms" required>
                        <label class="form-check-label" for="acceptTerms">J'accepte les conditions d'utilisation</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-person-add"></i> Créer mon compte
                    </button>
                </form>
                <p class="text-center mb-0">Déjà inscrit ? <a href="#" onclick="switchToLogin()">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../auth-ui.js"></script>
<script src="./js/language.js?v=<?= time(); ?>"></script>
<script src="./js/controls.js?v=<?= time(); ?>"></script>
<script src="./js/modals.js?v=<?= time(); ?>"></script>

<script>
// Delete article handler function
function deleteArticleHandler(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const btn = event.currentTarget || event.target.closest('.btn-delete-article');
    const articleId = btn.dataset.articleId;
    
    console.log('Delete handler called for article:', articleId);
    
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        return false;
    }
    
    btn.disabled = true;
    btn.style.opacity = '0.6';
    
    fetch('api_article.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&articleId=${articleId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Delete response:', data);
        if (data.success) {
            showSuccess('Article supprimé avec succès');
            // Redirect after showing success message
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        } else {
            showError(data.error || 'Erreur lors de la suppression');
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showError('Échec de la suppression: ' + error);
        btn.disabled = false;
        btn.style.opacity = '1';
    });
    
    return false;
}
</script>

<!-- jQuery and Bootstrap -->
<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>

</body>
</html>
