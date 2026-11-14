<?php

// auth.php - Gestion de l'authentification



// auth.php - Authentication (simplified)
require_once __DIR__ . '/../config.php';

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

        if ($email === '' || $password === '') {
            jsonResponse(['success' => false, 'message' => 'Email et mot de passe requis']);
        }

        // Try admins table first (new system)
        $stmt = $db->prepare("SELECT id, username, email, password, full_name, is_active FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() !== 1) {
            jsonResponse(['success' => false, 'message' => 'Aucun utilisateur trouvé avec cet email']);
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password hash
        if (!password_verify($password, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Mot de passe incorrect']);
        }

        // Check if active
        if ($user['is_active'] == 0) {
            jsonResponse(['success' => false, 'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.']);
        }

        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'user';

        jsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => 'user'
            ]
        ]);

    } elseif ($action === 'signup') {
        $full_name = trim($_POST['full_name'] ?? trim($_POST['prenom'] ?? '') . ' ' . trim($_POST['nom'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($full_name === '' || $email === '' || $password === '') {
            jsonResponse(['success' => false, 'message' => 'Tous les champs sont requis']);
        }

        // Check if email already exists
        $check = $db->prepare("SELECT id FROM admins WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            jsonResponse(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        }

        // Generate unique username
        $username = strtolower(str_replace(' ', '_', $full_name)) . '_' . uniqid();
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into admins table
        $insert = $db->prepare("INSERT INTO admins (username, email, password, full_name, is_active) VALUES (:username, :email, :password, :full_name, 1)");
        $ok = $insert->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashed_password,
            ':full_name' => $full_name
        ]);

        if (!$ok)
            jsonResponse(['success' => false, 'message' => 'Erreur lors de l\'inscription']);

        $user_id = $db->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'user';

        jsonResponse([
            'success' => true,
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user_id,
                'name' => $full_name,
                'email' => $email,
                'role' => 'user'
            ]
        ]);


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
