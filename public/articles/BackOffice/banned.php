<?php
session_start();
require_once __DIR__ . '/../app/utils/Language.php';
require_once __DIR__ . '/../app/config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// Get admin info
$adminStmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$adminStmt->execute([$_SESSION['admin_id']]);
$admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

$success = '';

// Handle unban
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unban_id'])) {
    $unbanId = intval($_POST['unban_id']);
    try {
        $pdo->prepare("DELETE FROM banned_users WHERE id = ?")->execute([$unbanId]);
        $success = '✅ Utilisateur débanni avec succès';
    } catch (Exception $e) {
        $success = '❌ Erreur lors du débannissement';
    }
}

// Get all banned users
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
    <title>GreenTech - <?= Language::get('banned_users_title') ?></title>
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
                <div class="alert <?= strpos($success, '✅') !== false ? 'alert-success' : 'alert-error' ?>"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!empty($bannedUsers)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Identifiant</th>
                                <th>Type</th>
                                <th>Raison du ban</th>
                                <th>Nombre de signalements</th>
                                <th>Banni par</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bannedUsers as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(substr($user['user_identifier'], 0, 30)) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?= ucfirst($user['user_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['reason']) ?></td>
                                    <td>
                                        <span class="badge badge-danger"><?= $user['report_count'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($user['banned_by_name'] ?? 'Système') ?></td>
                                    <td><?= getRelativeDate($user['banned_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Débannir cet utilisateur?')">
                                            <input type="hidden" name="unban_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn-small btn-approve">
                                                🔓 Débannir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px;">
                    <p style="color: var(--gray); font-size: 16px;">✅ Aucun utilisateur banni pour le moment</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
