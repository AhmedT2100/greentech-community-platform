<?php
session_start();
require_once __DIR__ . '/../app/utils/Language.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/utils/ActivityLogger.php';

$db = new Database();
$pdo = $db->getConnection();
$logger = new ActivityLogger($pdo);

// Get admin info (only if admin is logged in)
$admin = null;
if (isset($_SESSION['admin_id'])) {
    $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    try {
        // Get comment details for logging
        $commentStmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
        $commentStmt->execute([$deleteId]);
        $comment = $commentStmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            // Delete comment and all its replies (cascading)
            $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_comment_id = ?")->execute([$deleteId, $deleteId]);

            // Log activity
            $logger->log($_SESSION['admin_id'], 'delete', 'comment', $deleteId, "Suppression de commentaire", [
                'user_name' => $comment['user_name'],
                'article_id' => $comment['article_id'],
                'comment_length' => strlen($comment['comment'])
            ]);

            $success = '✅ Commentaire supprimé avec succès';
        }
    } catch (Exception $e) {
        $error = '❌ Erreur lors de la suppression';
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$articleFilter = isset($_GET['article_id']) ? intval($_GET['article_id']) : '';

// Get comments with search/filter AND report information
$query = "SELECT c.*, a.title as article_title, 
                 GROUP_CONCAT(r.reason SEPARATOR ' | ') as report_reasons,
                 COUNT(r.id) as report_count
          FROM comments c 
          LEFT JOIN articles a ON c.article_id = a.id
          LEFT JOIN reports r ON r.comment_id = c.id AND r.status = 'pending'
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (c.comment LIKE ? OR c.user_name LIKE ? OR a.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($articleFilter) {
    $query .= " AND c.article_id = ?";
    $params[] = $articleFilter;
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of articles for filter
$articlesStmt = $pdo->query("SELECT id, title FROM articles ORDER BY title");
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTech - Gestion des commentaires</title>
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
</head>
<body>

<div class="admin-container">
    <!-- Main Content -->
    <main class="main-content" style="margin-left: 0; width: 100%;">
        <a href="dashboard.php" style="display: inline-block; padding: 10px 15px; background: #0f766e; color: white; border-radius: 6px; text-decoration: none; font-size: 16px; transition: background 0.3s; margin: 5px;" onmouseover="this.style.background='#115e59'" onmouseout="this.style.background='#0f766e'" title="Retour au tableau de bord">
            ←
        </a>
        </div>

        <div class="dashboard-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Search & Filter Section -->
            <div class="search-filter-container">
                <form method="GET" class="search-filter-form">
                    <div class="filter-row">
                        <input type="text" name="search" placeholder="🔍 Rechercher par contenu, auteur ou article..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                    <div class="filter-row">
                        <select name="article_id" class="filter-select">
                            <option value="">📰 Tous les articles</option>
                            <?php foreach ($articles as $article): ?>
                                <option value="<?= $article['id'] ?>" <?= $articleFilter == $article['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(substr($article['title'], 0, 40)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary">🔎 Filtrer</button>
                        <a href="comments.php" class="btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 8px;">↺ Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Results count -->
            <div class="results-info">
                📊 <strong><?= count($comments) ?></strong> commentaire(s) trouvé(s)
            </div>

            <!-- Comments Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Auteur</th>
                            <th>Contenu</th>
                            <th>Article</th>
                            <th>Type</th>
                            <th>Signalements</th>
                            <th>Date</th>
                            <th>Likes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): 
                                $isReply = !empty($comment['parent_comment_id']);
                                $hasReports = !empty($comment['report_reasons']);
                            ?>
                                <tr style="<?= $hasReports ? 'background: #fef2f2; border-left: 3px solid #ef4444;' : ($isReply ? 'background: #f0fdf4; border-left: 3px solid #10b981;' : '') ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($comment['user_name'] ?? 'Anonymous') ?></strong>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($comment['comment'], 0, 50)) ?></small>
                                        <?php if (strlen($comment['comment']) > 50): ?>
                                            <span>...</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($comment['article_title'] ?? 'N/A', 0, 30)) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isReply ? 'badge-info' : 'badge-success' ?>">
                                            <?= $isReply ? '↳ Réponse' : '💬 Parent' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($hasReports): ?>
                                            <span class="badge badge-danger" title="<?= htmlspecialchars($comment['report_reasons']) ?>">
                                                🚩 <?= $comment['report_count'] ?> rapport(s)
                                            </span>
                                            <small style="display: block; margin-top: 5px; color: #dc2626; font-weight: 500;">
                                                <?= htmlspecialchars(substr($comment['report_reasons'], 0, 40)) ?><?= strlen($comment['report_reasons']) > 40 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getRelativeDate($comment['created_at']) ?></td>
                                    <td>
                                        <span class="badge badge-light">❤️ <?= $comment['likes'] ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce commentaire' + (<?= $isReply ? 'false' : 'true' ?> ? ' et ses réponses ?' : ' ?'))">
                                            <input type="hidden" name="delete_id" value="<?= $comment['id'] ?>">
                                            <button type="submit" class="btn-small btn-delete" title="Supprimer">🗑️ Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                    💭 Aucun commentaire trouvé
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>
