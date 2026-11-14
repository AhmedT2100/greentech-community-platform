<?php

// auth.php - Gestion de l'authentification



// auth.php - Authentication (simplified)
include 'config.php';

// Configure session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

session_start();

$database = new Database();
$db = $database->getConnection();

function jsonResponse($arr)
{
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Méthode non autorisée']);
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $providedCode = $_POST['unlock_code'] ?? '';

        if ($email === '') {
            jsonResponse(['success' => false, 'message' => 'Email requis']);
        }
        
        // Only require password if no unlock code is provided
        if ($password === '' && empty($providedCode)) {
             jsonResponse(['success' => false, 'message' => 'Mot de passe requis']);
        }


        // Check for new columns and add if missing (failed_attempts, unlock_code, unlock_code_expiry)
        try {
            $checkCols = $db->query("SELECT failed_attempts FROM utilisateur LIMIT 1");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1054) {
                $db->exec("ALTER TABLE utilisateur ADD COLUMN failed_attempts INT DEFAULT 0");
                $db->exec("ALTER TABLE utilisateur ADD COLUMN unlock_code VARCHAR(10) DEFAULT NULL");
                $db->exec("ALTER TABLE utilisateur ADD COLUMN unlock_code_expiry DATETIME DEFAULT NULL");
            }
        }

        // Fetch user with new columns
        $stmt = $db->prepare("SELECT id_user, nom, email, mot_de_passe, role, is_banned, failed_attempts, unlock_code, unlock_code_expiry FROM utilisateur WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() !== 1) {
            jsonResponse(['success' => false, 'message' => 'Aucun utilisateur trouvé avec cet email']);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check lock state
        if ($user['failed_attempts'] >= 3) {
            
            if (empty($providedCode)) {
                // User is locked but didn't provide code (re-attempting login)
                // Generate and RESEND a new code to ensure they have it
                $code = (string) rand(100000, 999999);
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $db->prepare("UPDATE utilisateur SET unlock_code = ?, unlock_code_expiry = ? WHERE id_user = ?")->execute([$code, $expiry, $user['id_user']]);

                require_once 'MailManager.php';
                $mailManager = new MailManager();
                $prenom = $user['prenom'] ?? explode(' ', $user['nom'])[0];
                $mailManager->sendUnlockCode($email, $user['nom'], $prenom, $code);

                jsonResponse(['success' => false, 'require_code' => true, 'message' => 'Compte verrouillé. Un nouveau code a été envoyé à votre email.']);
            }

            // Verify code
            if ($providedCode !== $user['unlock_code']) {
                jsonResponse(['success' => false, 'require_code' => true, 'message' => 'Code de déverrouillage incorrect.']);
            }

            if (strtotime($user['unlock_code_expiry']) < time()) {
                // Expired - Generate new one
                $code = (string) rand(100000, 999999);
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $db->prepare("UPDATE utilisateur SET unlock_code = ?, unlock_code_expiry = ? WHERE id_user = ?")->execute([$code, $expiry, $user['id_user']]);

                require_once 'MailManager.php';
                $mailManager = new MailManager();
                $prenom = $user['prenom'] ?? explode(' ', $user['nom'])[0];
                $mailManager->sendUnlockCode($email, $user['nom'], $prenom, $code);

                jsonResponse(['success' => false, 'require_code' => true, 'message' => 'Code expiré. Un nouveau code a été envoyé.']);
            }

            // Code good - Reset failed attempts, UNLOCK AND LOG USER IN
            $db->prepare("UPDATE utilisateur SET failed_attempts = 0, unlock_code = NULL, unlock_code_expiry = NULL WHERE id_user = ?")->execute([$user['id_user']]);
            
            // Check if banned
            if (isset($user['is_banned']) && $user['is_banned'] == 1) {
                jsonResponse(['success' => false, 'message' => 'Votre compte a été banni. Contactez l\'administrateur.']);
            }

            // Successful login via unlock code
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            jsonResponse([
                'success' => true,
                'message' => 'Compte déverrouillé et connexion réussie',
                'user' => [
                    'id' => $user['id_user'],
                    'nom' => $user['nom'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        }
        
        // If password was empty but account wasn't locked (or we fell through for some reason), we need password
        if ($password === '') {
             jsonResponse(['success' => false, 'message' => 'Mot de passe requis pour la connexion normale']);
        }

        if ($password !== $user['mot_de_passe']) {
            // Increment failed attempts
            $newAttempts = $user['failed_attempts'] + 1;
            $db->prepare("UPDATE utilisateur SET failed_attempts = ? WHERE id_user = ?")->execute([$newAttempts, $user['id_user']]);

            if ($newAttempts >= 3) {
                // Lock and send email
                $code = (string) rand(100000, 999999);
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $db->prepare("UPDATE utilisateur SET unlock_code = ?, unlock_code_expiry = ? WHERE id_user = ?")->execute([$code, $expiry, $user['id_user']]);

                require_once 'MailManager.php';
                $mailManager = new MailManager();
                // Ensure prenom exists, extract from name if not
                $prenom = $user['prenom'] ?? explode(' ', $user['nom'])[0];
                $mailManager->sendUnlockCode($email, $user['nom'], $prenom, $code);

                jsonResponse(['success' => false, 'require_code' => true, 'message' => '3 tentatives échouées. Un code de déverrouillage a été envoyé à votre email.']);
            }

            jsonResponse(['success' => false, 'message' => 'Mot de passe incorrect']);
        }

        // Check if banned
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            jsonResponse(['success' => false, 'message' => 'Votre compte a été banni. Contactez l\'administrateur.']);
        }

        // Reset failed attempts on success (if any existed < 3)
        if ($user['failed_attempts'] > 0) {
            $db->prepare("UPDATE utilisateur SET failed_attempts = 0 WHERE id_user = ?")->execute([$user['id_user']]);
        }

        // Successful login
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        jsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id_user'],
                'nom' => $user['nom'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);

    } elseif ($action === 'signup') {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($prenom === '' || $nom === '' || $email === '' || $password === '') {
            jsonResponse(['success' => false, 'message' => 'Tous les champs sont requis']);
        }

        $check = $db->prepare("SELECT id_user FROM utilisateur WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            jsonResponse(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        }

        $nom_complet = $prenom . ' ' . $nom;
        $countStmt = $db->query("SELECT COUNT(*) as total FROM utilisateur");
        $totalUsers = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $role = ($totalUsers === 0) ? 'admin' : 'user';

        $insert = $db->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mot_de_passe, :role)");
        $ok = $insert->execute([':nom' => $nom_complet, ':email' => $email, ':mot_de_passe' => $password, ':role' => $role]);

        if (!$ok)
            jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'inscription']);

        $user_id = $db->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $nom_complet;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        // 📧 ENVOYER L'EMAIL DE BIENVENUE
        try {
            require_once 'MailManager.php';
            $mailManager = new MailManager();
            $mailManager->sendWelcomeEmail($email, $nom, $prenom);
        } catch (Exception $e) {
            // L'email n'a pas pu être envoyé, mais l'inscription est réussie
            error_log("Erreur envoi email bienvenue: " . $e->getMessage());
        }

        jsonResponse(['success' => true, 'message' => 'Inscription réussie', 'user' => ['id' => $user_id, 'nom' => $nom_complet, 'email' => $email, 'role' => $role]]);


    } elseif ($action === 'logout') {
        session_destroy();
        jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
    }

    jsonResponse(['success' => false, 'message' => 'Action non reconnue']);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
