<?php
session_start();
require_once __DIR__ . '/../articles/app/utils/Language.php';
require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/controllers/ArticleController.php';
require_once __DIR__ . '/../articles/app/controllers/CommentController.php';
require_once __DIR__ . '/../articles/app/utils/ContentFilter.php';

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
$db = new Database();
$pdo = $db->getConnection();

// Controllers
$articleCtrl = new ArticleController($pdo);
$commentCtrl = new CommentController($pdo);

// Check if article ID is provided
if (!isset($_GET['id'])) {
    die('Article ID manquant');
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
$article = $articleCtrl->get($articleId);
if (!$article) {
    die('Article non trouvé');
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
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Vendor CSS Files -->
    <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    
    <!-- Main Template CSS -->
    <link href="../../public/css/style.css?v=<?= time(); ?>" rel="stylesheet">
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
                <li><a href="../contact.html">Contact</a></li>
                <li class="menu-active"><a href="index.php">Articles</a></li>
                <li id="loginLink"><a href="#" onclick="openLoginModal(); return false;">Connexion</a></li>
                <li id="signupLink"><a href="#" onclick="openSignupModal(); return false;">Inscription</a></li>
                <li id="adminLink" style="display:none;"><a href="../admin.html">Admin</a></li>
                <li id="userInfo" style="display:none;"></li>
            </ul>
        </nav>
    </div>
</header>
<!-- Header Section End -->

<!-- Hero/Breadcrumb Section -->
<section class="hero-section">
    <div class="hero-content">
        <nav class="breadcrumb-nav">
            <a href="../index.html">Home</a>
            <span></span>
            <a href="index.php">Articles</a>
            <span></span>
            <span><?= htmlspecialchars(substr($article['title'], 0, 30)) ?>...</span>
        </nav>
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
                <button type="button" class="btn-delete-article" data-article-id="<?= $articleId ?>" title="Supprimer l'article">🗑️ Supprimer</button>
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
                🚩 Signaler
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
                $commentUserId = $comment['user_id'] ?? 'guest';
                $commentUserName = $comment['user_name'] ?? 'Anonymous';
                $isOwner = $commentUserId == $currentUserId;
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
    const reason = document.getElementById('reportReason').value;
    const details = document.getElementById('reportDetails').value;
    const email = document.getElementById('reportEmail').value;
    
    // Send report
    fetch('../../api_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            article_id: <?= $articleId ?>,
            reason_type: reason,
            details: details,
            email: email
        })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              alert('Merci! Votre signalement a été envoyé.');
              closeReportModal();
              this.reset();
          } else {
              alert('Erreur lors de l\'envoi du signalement');
          }
      })
      .catch(error => console.error('Error:', error));
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
// Handle article delete button
document.addEventListener('click', function(e) {
    const deleteBtn = e.target.closest('.btn-delete-article');
    if (deleteBtn) {
        e.preventDefault();
        const articleId = deleteBtn.dataset.articleId;
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            deleteBtn.disabled = true;
            
            fetch('api_article.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&articleId=${articleId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Article supprimé avec succès');
                    location.href = 'index.php';
                } else {
                    alert(data.error || 'Erreur lors de la suppression');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Échec de la suppression');
            })
            .finally(() => {
                deleteBtn.disabled = false;
            });
        }
    }
});
</script>
</body>
</html>
