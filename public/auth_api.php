<?php
/**
 * API pour 2FA et Reset Password
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'MailManager.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Connexion échouée");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Connexion échouée']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'enable2FA':
        enable2FA($pdo);
        break;

    case 'disable2FA':
        disable2FA($pdo);
        break;

    case 'send2FACode':
        send2FACode($pdo);
        break;

    case 'verify2FACode':
        verify2FACode($pdo);
        break;

    case 'requestPasswordReset':
        requestPasswordReset($pdo);
        break;

    case 'verifyResetToken':
        verifyResetToken($pdo);
        break;

    case 'resetPassword':
        resetPassword($pdo);
        break;

    case 'sendWelcomeEmail':
        sendWelcomeEmail($pdo);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}

/**
 * Activer 2FA pour un utilisateur
 */
function enable2FA($pdo)
{
    $userId = $_POST['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'ID utilisateur requis']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = TRUE WHERE id = ?");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => '2FA activé avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Désactiver 2FA pour un utilisateur
 */
function disable2FA($pdo)
{
    $userId = $_POST['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'ID utilisateur requis']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = FALSE WHERE id = ?");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => '2FA désactivé avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Envoyer un code 2FA
 */
function send2FACode($pdo)
{
    $email = $_POST['email'] ?? null;

    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Email requis']);
        return;
    }

    try {
        // Récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT id, nom, prenom, two_factor_enabled FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
            return;
        }

        if (!$user['two_factor_enabled']) {
            echo json_encode(['success' => false, 'error' => '2FA non activé pour cet utilisateur']);
            return;
        }

        // Générer un code à 6 chiffres
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Expiration dans 10 minutes
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Sauvegarder le code
        $stmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $code, $expiresAt]);

        // Envoyer l'email
        $mailManager = new MailManager();
        $sent = $mailManager->send2FACode($email, $user['nom'], $user['prenom'], $code);

        if ($sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Code envoyé par email',
                'expiresIn' => 600 // 10 minutes en secondes
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Vérifier un code 2FA
 */
function verify2FACode($pdo)
{
    $email = $_POST['email'] ?? null;
    $code = $_POST['code'] ?? null;

    if (!$email || !$code) {
        echo json_encode(['success' => false, 'error' => 'Email et code requis']);
        return;
    }

    try {
        // Récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
            return;
        }

        // Vérifier le code
        $stmt = $pdo->prepare("SELECT id FROM two_factor_codes WHERE user_id = ? AND code = ? AND expires_at > NOW() AND used = FALSE ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user['id'], $code]);
        $codeRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$codeRecord) {
            echo json_encode(['success' => false, 'error' => 'Code invalide ou expiré']);
            return;
        }

        // Marquer le code comme utilisé
        $stmt = $pdo->prepare("UPDATE two_factor_codes SET used = TRUE WHERE id = ?");
        $stmt->execute([$codeRecord['id']]);

        echo json_encode(['success' => true, 'message' => 'Code vérifié avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Demander une réinitialisation de mot de passe
 */
function requestPasswordReset($pdo)
{
    $email = $_POST['email'] ?? null;

    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Email requis']);
        return;
    }

    try {
        // Récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Pour des raisons de sécurité, on ne dit pas si l'email existe ou non
            echo json_encode(['success' => true, 'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé']);
            return;
        }

        // Générer un token unique
        $token = bin2hex(random_bytes(32));

        // Expiration dans 1 heure
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Sauvegarder le token
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expiresAt]);

        // Envoyer l'email
        $mailManager = new MailManager();
        $sent = $mailManager->sendPasswordResetEmail($email, $user['nom'], $user['prenom'], $token);

        if ($sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Email de réinitialisation envoyé'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Vérifier un token de réinitialisation
 */
function verifyResetToken($pdo)
{
    $token = $_POST['token'] ?? $_GET['token'] ?? null;

    if (!$token) {
        echo json_encode(['success' => false, 'error' => 'Token requis']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = FALSE");
        $stmt->execute([$token]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            echo json_encode(['success' => false, 'error' => 'Token invalide ou expiré']);
            return;
        }

        echo json_encode(['success' => true, 'userId' => $tokenRecord['user_id']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Réinitialiser le mot de passe
 */
function resetPassword($pdo)
{
    $token = $_POST['token'] ?? null;
    $newPassword = $_POST['newPassword'] ?? null;

    if (!$token || !$newPassword) {
        echo json_encode(['success' => false, 'error' => 'Token et nouveau mot de passe requis']);
        return;
    }

    try {
        // Vérifier le token
        $stmt = $pdo->prepare("SELECT user_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = FALSE");
        $stmt->execute([$token]);
        $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRecord) {
            echo json_encode(['success' => false, 'error' => 'Token invalide ou expiré']);
            return;
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $tokenRecord['user_id']]);

        // Marquer le token comme utilisé
        $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE token = ?");
        $stmt->execute([$token]);

        echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Envoyer un email de bienvenue
 */
function sendWelcomeEmail($pdo)
{
    $userId = $_POST['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'ID utilisateur requis']);
        return;
    }

    try {
        // Récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
            return;
        }

        // Envoyer l'email
        $mailManager = new MailManager();
        $sent = $mailManager->sendWelcomeEmail($user['email'], $user['nom'], $user['prenom']);

        if ($sent) {
            echo json_encode(['success' => true, 'message' => 'Email de bienvenue envoyé']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>