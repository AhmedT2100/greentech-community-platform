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

// Handle create new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if ($username && $email && $password && $confirm_password && $full_name) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 8) {
                try {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword, $full_name]);
                    
                    // Get the ID of the newly created admin
                    $newAdminId = $pdo->lastInsertId();
                    
                    // Log activity
                    $logger->log($_SESSION['admin_id'], 'create', 'admin', $newAdminId, "Création d'admin", [
                        'username' => $username,
                        'email' => $email,
                        'full_name' => $full_name
                    ]);
                    
                    $success = '✅ Administrateur créé avec succès';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = '❌ Ce nom d\'utilisateur ou cet email existe déjà';
                    } else {
                        $error = '❌ Erreur lors de la création';
                    }
                }
            } else {
                $error = '❌ Le mot de passe doit contenir au moins 8 caractères';
            }
        } else {
            $error = '❌ Les mots de passe ne correspondent pas';
        }
    } else {
        $error = '❌ Veuillez remplir tous les champs';
    }
}

// Handle deactivate admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_id'])) {
    $deactivateId = intval($_POST['deactivate_id']);
    
    // Don't allow deactivating the current admin
    if ($deactivateId !== $_SESSION['admin_id']) {
        try {
            $stmtDeactivate = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
            $stmtDeactivate->execute([$deactivateId]);
            $adminToDeactivate = $stmtDeactivate->fetch(PDO::FETCH_ASSOC);
            
            $pdo->prepare("UPDATE admins SET is_active = 0 WHERE id = ?")->execute([$deactivateId]);
            
            // Log activity
            $logger->log($_SESSION['admin_id'], 'update', 'admin', $deactivateId, "Désactivation d'admin", [
                'username' => $adminToDeactivate['username'] ?? 'N/A'
            ]);
            
            $success = '✅ Administrateur désactivé';
        } catch (Exception $e) {
            $error = '❌ Erreur lors de la désactivation';
        }
    } else {
        $error = '❌ Vous ne pouvez pas vous désactiver vous-même';
    }
}

// Handle reactivate admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate_id'])) {
    $reactivateId = intval($_POST['reactivate_id']);
    try {
        $stmtReactivate = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
        $stmtReactivate->execute([$reactivateId]);
        $adminToReactivate = $stmtReactivate->fetch(PDO::FETCH_ASSOC);
        
        $pdo->prepare("UPDATE admins SET is_active = 1 WHERE id = ?")->execute([$reactivateId]);
        
        // Log activity
        $logger->log($_SESSION['admin_id'], 'update', 'admin', $reactivateId, "Réactivation d'admin", [
            'username' => $adminToReactivate['username'] ?? 'N/A'
        ]);
        
        $success = '✅ Administrateur réactivé';
    } catch (Exception $e) {
        $error = '❌ Erreur lors de la réactivation';
    }
}

// Get all admins
$adminsStmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>GreenTech - <?= Language::get('manage_admins_title') ?></title>
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

            <!-- Create New Admin Form -->
            <div class="form-card">
                <h2>➕ Créer un nouveau administrateur</h2>
                <form method="POST" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom complet</label>
                            <input type="text" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Nom d'utilisateur</label>
                            <input type="text" name="username" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Mot de passe (min. 8 caractères)</label>
                            <input type="password" name="password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" required minlength="8">
                        </div>
                    </div>

                    <button type="submit" name="create_admin" class="btn-primary">✅ Créer l'administrateur</button>
                </form>
            </div>

            <!-- Admins Table -->
            <div style="margin-top: 40px;">
                <h2 style="margin-bottom: 20px; color: var(--dark);">📋 Liste des administrateurs (<?= count($admins) ?>)</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom complet</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Créé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $adminUser): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($adminUser['full_name']) ?></strong>
                                        <?php if ($adminUser['id'] === $_SESSION['admin_id']): ?>
                                            <span class="badge badge-success"> (Vous)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($adminUser['username']) ?></td>
                                    <td><?= htmlspecialchars($adminUser['email']) ?></td>
                                    <td>
                                        <?php if ($adminUser['is_active']): ?>
                                            <span class="badge badge-success">✅ Actif</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fee2e2; color: #991b1b;">❌ Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $adminUser['last_login'] ? getRelativeDate($adminUser['last_login']) : 'Jamais' ?>
                                    </td>
                                    <td><?= getRelativeDate($adminUser['created_at']) ?></td>
                                    <td>
                                        <?php if ($adminUser['id'] !== $_SESSION['admin_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="<?= $adminUser['is_active'] ? 'deactivate_id' : 'reactivate_id' ?>" value="<?= $adminUser['id'] ?>">
                                                <button type="submit" class="btn-small" style="background: <?= $adminUser['is_active'] ? '#fee2e2' : '#dcfce7' ?>; color: <?= $adminUser['is_active'] ? '#991b1b' : '#166534' ?>;">
                                                    <?= $adminUser['is_active'] ? '🔒 Désactiver' : '🔓 Réactiver' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--gray); font-size: 12px;">Compte actif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
