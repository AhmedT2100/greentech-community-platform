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

$success = '';
$error = '';

// Handle resolve report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $reportId = intval($_POST['resolve_id']);
    $action = $_POST['action'] ?? 'dismiss';
    
    try {
        $logDetails = ['action_taken' => $action];
        
        if ($action === 'delete_article') {
            // Get article ID from report
            $reportStmt = $pdo->prepare("SELECT article_id FROM reports WHERE id = ?");
            $reportStmt->execute([$reportId]);
            $report = $reportStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report && $report['article_id']) {
                // Delete article and associated comments
                $pdo->prepare("DELETE FROM comments WHERE article_id = ?")->execute([$report['article_id']]);
                $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$report['article_id']]);
                $logDetails['article_id'] = $report['article_id'];
            }
        } elseif ($action === 'delete_comment') {
            // Get comment ID from report
            $reportStmt = $pdo->prepare("SELECT comment_id FROM reports WHERE id = ?");
            $reportStmt->execute([$reportId]);
            $report = $reportStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report && $report['comment_id']) {
                $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$report['comment_id']]);
                $logDetails['comment_id'] = $report['comment_id'];
            }
        } elseif ($action === 'ban_user') {
            // Get reporter info and ban them
            $reportStmt = $pdo->prepare("SELECT reported_by FROM reports WHERE id = ?");
            $reportStmt->execute([$reportId]);
            $report = $reportStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report && $report['reported_by']) {
                $banStmt = $pdo->prepare("INSERT IGNORE INTO banned_users (user_identifier, user_type, reason, report_count, banned_by) VALUES (?, 'email', 'Multiple reports', 1, ?)");
                $banStmt->execute([$report['reported_by'], $_SESSION['admin_id']]);
                $logDetails['banned_user'] = $report['reported_by'];
            }
        }
        
        // Update report status
        $updateStmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $updateStmt->execute([$_SESSION['admin_id'], $reportId]);
        
        // Log activity
        $logger->log($_SESSION['admin_id'], 'resolve', 'report', $reportId, "Résolution de signalement", $logDetails);
        
        $success = '✅ Rapport traité avec succès';
    } catch (Exception $e) {
        $error = '❌ Erreur lors du traitement';
    }
}

// Get pending reports
$reportsStmt = $pdo->query("
    SELECT r.id,
           r.report_type,
           r.article_id,
           r.comment_id,
           r.reason,
           r.reported_by,
           r.created_at,
           a.title as article_title,
           c.comment as comment_text,
           GROUP_CONCAT(r2.reason SEPARATOR ' | ') as all_reasons,
           COUNT(r2.id) as total_reports
    FROM reports r
    LEFT JOIN articles a ON r.article_id = a.id
    LEFT JOIN comments c ON r.comment_id = c.id
    LEFT JOIN reports r2 ON (r.report_type = r2.report_type AND 
                            ((r.report_type = 'article' AND r.article_id = r2.article_id) OR 
                             (r.report_type = 'comment' AND r.comment_id = r2.comment_id)))
                           AND r2.status = 'pending'
    WHERE r.status = 'pending'
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get banned users
$bannedStmt = $pdo->query("
    SELECT b.*, 
           a.full_name as banned_by_name
    FROM banned_users b
    LEFT JOIN admins a ON b.banned_by = a.id
    ORDER BY b.banned_at DESC
");
$bannedUsers = $bannedStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>GreenTech - <?= Language::get('manage_reports_title') ?></title>
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
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Pending Reports -->
            <div style="margin-bottom: 40px;">
                <h2 style="margin-bottom: 20px; color: var(--dark);">📋 Signalements en attente (<?= count($reports) ?>)</h2>
                
                <?php if (!empty($reports)): ?>
                    <div class="reports-grid">
                        <?php foreach ($reports as $report): ?>
                            <div class="report-card">
                                <div class="report-header">
                                    <h3><?= htmlspecialchars($report['all_reasons']) ?></h3>
                                    <span class="report-type">
                                        <?= $report['report_type'] === 'article' ? '📰 Article' : '💬 Commentaire' ?>
                                    </span>
                                </div>

                                <div class="report-content">
                                    <?php if ($report['report_type'] === 'article'): ?>
                                        <p><strong>Article:</strong> <?= htmlspecialchars($report['article_title']) ?></p>
                                    <?php else: ?>
                                        <p><strong>Commentaire:</strong></p>
                                        <p style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-top: 8px; border-left: 3px solid #3b82f6;">
                                            <?= htmlspecialchars($report['comment_text']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p><strong>Signalé par:</strong> <?= htmlspecialchars($report['reported_by']) ?></p>
                                    <p><strong>Date:</strong> <?= getRelativeDate($report['created_at']) ?></p>
                                    
                                    <p><strong>Raisons de signalement (<?= $report['total_reports'] ?>):</strong></p>
                                    <p style="background: #fef2f2; border-left: 3px solid #ef4444; padding: 12px; border-radius: 6px; margin-top: 8px; color: #dc2626;">
                                        <?= htmlspecialchars($report['all_reasons']) ?>
                                    </p>
                                </div>

                                <div class="report-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="resolve_id" value="<?= $report['id'] ?>">
                                        <input type="hidden" name="action" value="delete_<?= $report['report_type'] ?>">
                                        <button type="submit" class="btn-small btn-delete" onclick="return confirm('Supprimer le <?= $report['report_type'] === 'article' ? 'contenu' : 'commentaire' ?>?')">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="resolve_id" value="<?= $report['id'] ?>">
                                        <input type="hidden" name="action" value="dismiss">
                                        <button type="submit" class="btn-small" style="background: #cbd5e1; color: #1e293b;">
                                            ✓ Rejeter
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: white; border-radius: 12px;">
                        <p style="color: var(--gray); font-size: 16px;">✅ Aucun signalement en attente</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Banned Users -->
            <div>
                <h2 style="margin-bottom: 20px; color: var(--dark);">🔒 Utilisateurs bannis (<?= count($bannedUsers) ?>)</h2>
                
                <?php if (!empty($bannedUsers)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Identifiant</th>
                                    <th>Type</th>
                                    <th>Raison</th>
                                    <th>Signalements</th>
                                    <th>Banni par</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bannedUsers as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_identifier']) ?></td>
                                        <td><?= ucfirst($user['user_type']) ?></td>
                                        <td><?= htmlspecialchars($user['reason']) ?></td>
                                        <td><span class="badge badge-warning"><?= $user['report_count'] ?></span></td>
                                        <td><?= htmlspecialchars($user['banned_by_name'] ?? 'Système') ?></td>
                                        <td><?= getRelativeDate($user['banned_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: white; border-radius: 12px;">
                        <p style="color: var(--gray); font-size: 16px;">✅ Aucun utilisateur banni</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>
