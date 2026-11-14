<?php
session_start();
require_once __DIR__ . '/../app/utils/Language.php';
require_once __DIR__ . '/../app/config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Get admin info (only if admin is logged in)
$admin = null;
if (isset($_SESSION['admin_id'])) {
    $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
}

// Get statistics
$totalArticles = $pdo->query("SELECT COUNT(*) as count FROM articles")->fetch(PDO::FETCH_ASSOC)['count'];
$totalComments = $pdo->query("SELECT COUNT(*) as count FROM comments")->fetch(PDO::FETCH_ASSOC)['count'];
$totalReports = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'];
$totalAdmins = $pdo->query("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['count'];
$totalBanned = $pdo->query("SELECT COUNT(*) as count FROM banned_users")->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent articles
$recentArticles = $pdo->query("
    SELECT id, title, created_at, likes 
    FROM articles 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent reports
$recentReports = $pdo->query("
    SELECT r.id,
           r.report_type,
           r.article_id,
           r.comment_id,
           r.reason,
           r.reported_by,
           r.created_at,
           CASE 
               WHEN r.report_type = 'article' THEN a.title
               WHEN r.report_type = 'comment' THEN CONCAT('Commentaire sur: ', a.title)
           END as subject,
           a.title as article_title,
           GROUP_CONCAT(r2.reason SEPARATOR ' | ') as all_reasons,
           COUNT(r2.id) as total_reports
    FROM reports r
    LEFT JOIN articles a ON (r.report_type = 'article' AND r.article_id = a.id) OR (r.report_type = 'comment' AND r.comment_id IN (SELECT id FROM comments WHERE article_id = a.id))
    LEFT JOIN reports r2 ON (r.report_type = r2.report_type AND 
                            ((r.report_type = 'article' AND r.article_id = r2.article_id) OR 
                             (r.report_type = 'comment' AND r.comment_id = r2.comment_id)))
                           AND r2.status = 'pending'
    WHERE r.status = 'pending'
    GROUP BY r.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

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
    <title>GreenTech - Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
</head>
<body>

<div class="admin-container">
    <!-- Main Content -->
    <main class="main-content" style="margin-left: 0; width: 100%;">
        <div class="dashboard-header">
            <h1>📊 <?= Language::get('dashboard') ?></h1>
            <p><?= sprintf(Language::get('welcome'), htmlspecialchars($admin['full_name'] ?? $admin['username'])) ?></p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <a href="articles.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-icon">📰</div>
                <div class="stat-content">
                    <h3><?= $totalArticles ?></h3>
                    <p><?= Language::get('articles_published') ?></p>
                </div>
            </a>

            <a href="comments.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-icon">💬</div>
                <div class="stat-content">
                    <h3><?= $totalComments ?></h3>
                    <p><?= Language::get('comments') ?></p>
                </div>
            </a>

            <a href="reports.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-icon">🚨</div>
                <div class="stat-content stat-warning">
                    <h3><?= $totalReports ?></h3>
                    <p><?= Language::get('pending_reports') ?></p>
                </div>
            </a>

            <a href="analytics.php" class="stat-card" style="text-decoration: none; color: inherit; cursor: pointer;">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <h3>📊</h3>
                    <p><?= Language::get('analytics') ?></p>
                </div>
            </a>
        </div>

        <!-- Recent Activity Section -->
        <div class="activity-section">
            <div class="section-grid">
                <!-- Recent Articles -->
                <div class="activity-card">
                    <h2><?= Language::get('recent_articles') ?></h2>
                    <?php if (!empty($recentArticles)): ?>
                        <div class="activity-list">
                            <?php foreach ($recentArticles as $article): ?>
                                <div class="activity-item">
                                    <div class="activity-title">
                                        <h3><?= htmlspecialchars($article['title']) ?></h3>
                                        <p><?= getRelativeDate($article['created_at']) ?></p>
                                    </div>
                                    <div class="activity-meta">
                                        <span>❤️ <?= $article['likes'] ?></span>
                                        <a href="articles.php?edit=<?= $article['id'] ?>" class="link-edit">Modifier</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Aucun article pour le moment</p>
                    <?php endif; ?>
                </div>

                <!-- Pending Reports -->
                <div class="activity-card">
                    <h2><?= Language::get('recent_reports') ?></h2>
                    <?php if (!empty($recentReports)): ?>
                        <div class="activity-list">
                            <?php foreach ($recentReports as $report): ?>
                                <div class="activity-item">
                                    <div class="activity-title">
                                        <h3><?= htmlspecialchars($report['all_reasons'] ?? $report['reason']) ?></h3>
                                        <p><?= htmlspecialchars($report['article_title'] ?? Language::get('comments')) ?></p>
                                        <small style="color: #666; margin-top: 4px;">Total: <?= $report['total_reports'] ?> rapport(s)</small>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="badge badge-warning"><?= Language::get('pending_reports') ?></span>
                                        <a href="reports.php?id=<?= $report['id'] ?>" class="link-view">Voir</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">✅ <?= Language::get('no_reports') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><?= Language::get('quick_actions') ?></h2>
            <div class="action-buttons">
                <a href="articles.php" class="action-btn">
                    <span>📰</span>
                    <span><?= Language::get('manage_articles') ?></span>
                </a>
                <a href="reports.php" class="action-btn">
                    <span>🚨</span>
                    <span><?= Language::get('view_reports') ?></span>
                </a>
                <!-- ADMINISTATEUR - Commented out
                <a href="admins.php" class="action-btn">
                    <span>👥</span>
                    <span><?= Language::get('manage_admins') ?></span>
                </a>
                -->
                <!-- UTILISATEURS BANNIS - Commented out
                <a href="banned.php" class="action-btn">
                    <span>🔒</span>
                    <span><?= Language::get('manage_banned') ?></span>
                </a>
                -->
            </div>
        </div>
    </main>
</div>

<script src="js/language.js?v=<?= time(); ?>"></script>
</body>
</html>
