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

// Get period filter
$period = isset($_GET['period']) ? trim($_GET['period']) : '30';
$periodLabel = [
    '7' => 'Derniers 7 jours',
    '30' => 'Dernier mois',
    '90' => 'Dernier trimestre',
    '365' => 'Dernière année'
][$period] ?? 'Dernier mois';

// Get article analytics
$topArticles = $pdo->prepare("
    SELECT id, title, likes, created_at,
           (SELECT COUNT(*) FROM comments WHERE article_id = articles.id) as comment_count
    FROM articles
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY likes DESC
    LIMIT 10
");
$topArticles->execute([$period]);
$topArticles = $topArticles->fetchAll(PDO::FETCH_ASSOC);

// Get most commented articles
$mostCommented = $pdo->prepare("
    SELECT a.id, a.title, COUNT(c.id) as comment_count, a.likes
    FROM articles a
    LEFT JOIN comments c ON a.id = c.article_id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY a.id
    ORDER BY comment_count DESC
    LIMIT 10
");
$mostCommented->execute([$period]);
$mostCommented = $mostCommented->fetchAll(PDO::FETCH_ASSOC);

// Get engagement metrics
$engagementStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT a.id) as total_articles,
        COUNT(DISTINCT c.id) as total_comments,
        SUM(a.likes) as total_likes,
        AVG(a.likes) as avg_likes_per_article,
        COUNT(DISTINCT c.id) / NULLIF(COUNT(DISTINCT a.id), 0) as avg_comments_per_article
    FROM articles a
    LEFT JOIN comments c ON a.id = c.article_id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
");
$engagementStats->execute([$period]);
$stats = $engagementStats->fetch(PDO::FETCH_ASSOC);

// Get activity logs statistics
$activityStats = $logger->getStatistics($period);

// Get activity heatmap for chart
$activityHeatmap = $logger->getActivityHeatmap($period);

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
    <title>GreenTech - <?= Language::get('analytics') ?></title>
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
            <!-- Period Filter -->
            <div class="filter-period">
                <label>Période:</label>
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <select name="period" onchange="this.form.submit()" class="period-select">
                        <option value="7" <?= $period == 7 ? 'selected' : '' ?>>Derniers 7 jours</option>
                        <option value="30" <?= $period == 30 ? 'selected' : '' ?>>Dernier mois</option>
                        <option value="90" <?= $period == 90 ? 'selected' : '' ?>>Dernier trimestre</option>
                        <option value="365" <?= $period == 365 ? 'selected' : '' ?>>Dernière année</option>
                    </select>
                </form>
            </div>

            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon">📰</div>
                    <div class="metric-content">
                        <p class="metric-label">Articles publiés</p>
                        <p class="metric-value"><?= $stats['total_articles'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">💬</div>
                    <div class="metric-content">
                        <p class="metric-label">Commentaires totaux</p>
                        <p class="metric-value"><?= $stats['total_comments'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">❤️</div>
                    <div class="metric-content">
                        <p class="metric-label">Total des likes</p>
                        <p class="metric-value"><?= $stats['total_likes'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">⭐</div>
                    <div class="metric-content">
                        <p class="metric-label">Likes par article (moy.)</p>
                        <p class="metric-value"><?= round($stats['avg_likes_per_article'] ?? 0, 1) ?></p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">💭</div>
                    <div class="metric-content">
                        <p class="metric-label">Comments par article (moy.)</p>
                        <p class="metric-value"><?= round($stats['avg_comments_per_article'] ?? 0, 1) ?></p>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">📊</div>
                    <div class="metric-content">
                        <p class="metric-label">Taux d'engagement</p>
                        <p class="metric-value">
                            <?php
                            $engagement = ($stats['total_comments'] > 0 && $stats['total_articles'] > 0) 
                                ? round(($stats['total_comments'] * 100) / $stats['total_articles'], 1)
                                : 0;
                            echo $engagement . '%';
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <!-- Activity Chart -->
                <div class="chart-container">
                    <h3>📊 Activité sur <?= $periodLabel ?></h3>
                    <canvas id="activityChart"></canvas>
                </div>

                <!-- Actions Distribution -->
                <div class="chart-container">
                    <h3>🔧 Distribution des actions admin</h3>
                    <canvas id="actionsChart"></canvas>
                </div>
            </div>

            <!-- Top Articles -->
            <div class="analytics-section">
                <h2>🏆 Articles les plus populaires</h2>
                <div class="articles-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Likes ❤️</th>
                                <th>Commentaires 💬</th>
                                <th>Engagement</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topArticles)): ?>
                                <?php foreach ($topArticles as $article): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(substr($article['title'], 0, 50)) ?></strong></td>
                                        <td><span class="badge badge-success">❤️ <?= $article['likes'] ?? 0 ?></span></td>
                                        <td><span class="badge badge-info">💬 <?= $article['comment_count'] ?? 0 ?></span></td>
                                        <td>
                                            <?php 
                                            $total_engagement = ($article['likes'] ?? 0) + ($article['comment_count'] ?? 0);
                                            echo $total_engagement;
                                            ?>
                                        </td>
                                        <td><?= getRelativeDate($article['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">
                                        📭 Aucun article sur cette période
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Most Commented Articles -->
            <div class="analytics-section">
                <h2>💬 Articles les plus commentés</h2>
                <div class="articles-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Commentaires</th>
                                <th>Likes</th>
                                <th>Taux de commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($mostCommented)): ?>
                                <?php foreach ($mostCommented as $article): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(substr($article['title'], 0, 50)) ?></strong></td>
                                        <td><span class="badge badge-info">💬 <?= $article['comment_count'] ?></span></td>
                                        <td><span class="badge badge-success">❤️ <?= $article['likes'] ?? 0 ?></span></td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?php echo $article['comment_count'] > 0 ? '⬆️' : '⬇️'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">
                                        📭 Aucun commentaire sur cette période
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Admin Activity -->
            <?php if (!empty($activityStats['most_active_admins'])): ?>
            <div class="analytics-section">
                <h2>👥 Admins les plus actifs</h2>
                <div class="articles-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Nombre d'actions</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalActions = $activityStats['total_actions'] ?? 1;
                            foreach ($activityStats['most_active_admins'] as $adminActivity):
                            ?>
                                <tr>
                                    <td>👤 <?= htmlspecialchars($adminActivity['username'] ?? 'Inconnu') ?></td>
                                    <td><?= $adminActivity['action_count'] ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= round(($adminActivity['action_count'] * 100) / $totalActions, 1) ?>%;">
                                                <?= round(($adminActivity['action_count'] * 100) / $totalActions, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // Pass data to analytics.js
    window.activityData = <?= json_encode($activityHeatmap) ?>;
    window.actionStats = <?= json_encode($activityStats['actions_by_type'] ?? []) ?>;
</script>
<script src="js/analytics.js"></script>
</body>
</html>
