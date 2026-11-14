<?php
session_start();
require_once __DIR__ . '/../app/utils/Language.php';
require_once __DIR__ . '/../app/config/Database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $db = new Database();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);

                // Set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];

                header('Location: dashboard.php');
                exit;
            } else {
                $error = '❌ Nom d\'utilisateur ou mot de passe incorrect';
            }
        } catch (PDOException $e) {
            $error = '❌ Erreur de connexion à la base de données';
        }
    } else {
        $error = '❌ Veuillez remplir tous les champs';
    }
}

// Handle first admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if ($username && $email && $password && $confirm_password && $full_name) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 8) {
                try {
                    $db = new Database();
                    $pdo = $db->getConnection();

                    // Always allow first admin creation (no count check)
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword, $full_name]);

                    $success = '✅ Compte administrateur créé avec succès! Vous pouvez maintenant vous connecter.';
                    // Don't show registration form after success
                    $hasAdmins = true;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $error = '❌ Ce nom d\'utilisateur ou cet email existe déjà';
                    } else {
                        $error = '❌ Erreur lors de la création du compte';
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

// Check if there are any admins
$hasAdmins = false;
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    $hasAdmins = $count > 0;
} catch (Exception $e) {
    // Table might not exist
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTech - Admin Login</title>
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h1>🌱 GreenTech Admin</h1>
        <p><?= Language::get('admin_dashboard') ?></p>
    </div>

    <div class="login-content">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <script>
                setTimeout(() => {
                    window.location.href = 'login.php?refresh=' + Date.now();
                }, 2000);
            </script>
        <?php endif; ?>

        <?php if (!$hasAdmins): ?>
            <!-- First Admin Registration -->
            <div class="tab-content active">
                <h2 style="font-size: 18px; margin-bottom: 20px; color: #1e293b;">Créer le premier administrateur</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe (min. 8 caractères)</label>
                        <input type="password" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirmer le mot de passe</label>
                        <input type="password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" name="register" class="btn-login" style="width: 100%; margin-top: 20px;">
                        ✅ Créer le compte
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Admin Login -->
            <form method="POST">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn-login" style="width: 100%; margin-top: 20px;">
                    🔓 Se connecter
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
