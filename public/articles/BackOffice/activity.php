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

// Get filter parameters
$actionFilter = isset($_GET['action']) ? trim($_GET['action']) : '';
$adminFilter = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : '';
$entityFilter = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : '';

// Build filters array
$filters = [];
if ($actionFilter) $filters['action'] = $actionFilter;
if ($adminFilter) $filters['admin_id'] = $adminFilter;
if ($entityFilter) $filters['entity_type'] = $entityFilter;

// Get activity logs
$logs = $logger->getRecentLogs(500, $filters);

// Get list of all admins for filter dropdown
$adminsStmt = $pdo->query("SELECT id, username FROM admins ORDER BY username ASC");
$admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get available actions
$actionsStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get available entity types
$entitiesStmt = $pdo->query("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type ASC");
$entities = $entitiesStmt->fetchAll(PDO::FETCH_ASSOC);

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
        return date('d/m/Y H:i', strtotime($dateString));
    }
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTech - <?= Language::get('activity_logs') ?></title>
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
            <!-- Filter Section -->
            <div class="filter-container">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <select name="admin_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">👥 Tous les admins</option>
                            <?php foreach ($admins as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $adminFilter == $a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="action" class="filter-select" onchange="this.form.submit()">
                            <option value="">🔧 Toutes les actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= $action['action'] ?>" <?= $actionFilter == $action['action'] ? 'selected' : '' ?>>
                                    <?php 
                                    $actionLabels = [
                                        'create' => 'Créer',
                                        'update' => 'Modifier',
                                        'delete' => 'Supprimer',
                                        'resolve' => 'Résoudre',
                                        'view' => 'Consulter'
                                    ];
                                    echo $actionLabels[$action['action']] ?? $action['action'];
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="entity_type" class="filter-select" onchange="this.form.submit()">
                            <option value="">📦 Tous les types</option>
                            <?php foreach ($entities as $entity): ?>
                                <option value="<?= $entity['entity_type'] ?>" <?= $entityFilter == $entity['entity_type'] ? 'selected' : '' ?>>
                                    <?= ucfirst($entity['entity_type']) ?>s
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <a href="activity.php" class="btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 8px;">↺ Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                📊 <strong><?= count($logs) ?></strong> entrée(s) trouvée(s)
            </div>

            <!-- Activity Logs Table -->
            <div class="table-container activity-logs-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Entité ID</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small><?= getRelativeDate($log['created_at']) ?></small><br>
                                        <tiny style="color: #94a3b8;"><?= date('H:i:s', strtotime($log['created_at'])) ?></tiny>
                                    </td>
                                    <td>
                                        👤 <?= htmlspecialchars($log['username'] ?? 'Admin #' . $log['admin_id']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actionLabels = [
                                            'create' => ['🔵 Créer', '#3b82f6'],
                                            'update' => ['🟡 Modifier', '#f59e0b'],
                                            'delete' => ['🔴 Supprimer', '#ef4444'],
                                            'resolve' => ['🟢 Résoudre', '#16a34a'],
                                            'view' => ['⚪ Consulter', '#64748b']
                                        ];
                                        $action = $log['action'];
                                        $label = $actionLabels[$action] ?? [$action, '#64748b'];
                                        ?>
                                        <span class="action-badge" style="background-color: <?= $label[1] ?>20; color: <?= $label[1] ?>;">
                                            <?= $label[0] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="entity-badge">
                                            <?php
                                            $entityIcons = [
                                                'article' => '📰',
                                                'comment' => '💬',
                                                'report' => '🚨',
                                                'admin' => '👥',
                                                'user' => '👤'
                                            ];
                                            echo ($entityIcons[$log['entity_type']] ?? '📦') . ' ' . ucfirst($log['entity_type']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($log['entity_id']): ?>
                                            <code>#<?= $log['entity_id'] ?></code>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($log['description']) ?></small>
                                        <?php if ($log['details']): ?>
                                            <details style="cursor: pointer; font-size: 12px; color: #64748b;">
                                                <summary>📋 Détails</summary>
                                                <pre style="background: #f1f5f9; padding: 8px; border-radius: 4px; margin-top: 8px; overflow-x: auto;">
<?php
$details = json_decode($log['details'], true);
echo json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
                                                </pre>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small style="font-family: monospace; color: #94a3b8;">
                                            <?= htmlspecialchars($log['ip_address']) ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                    📭 Aucune activité trouvée
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
