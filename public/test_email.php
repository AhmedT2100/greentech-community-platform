<?php
/**
 * Test simple d'envoi d'email
 */

// Désactiver les headers de config.php
$_SERVER['HTTP_ACCEPT'] = 'text/html';

require_once 'MailManager.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Email - GreenTechHub</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .test-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .result {
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class='test-container'>
        <div class='text-center mb-4'>
            <i class='fas fa-envelope' style='font-size: 60px; color: #667eea;'></i>
            <h1 class='mt-3'>Test d'envoi d'email</h1>
        </div>";

// Récupérer l'email depuis le formulaire ou utiliser celui configuré
$testEmail = $_POST['testEmail'] ?? 'mohamednour3.x@gmail.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='result'>";

    try {
        $mailManager = new MailManager();
        $sent = $mailManager->sendWelcomeEmail($testEmail, 'Test', 'Utilisateur');

        if ($sent) {
            echo "<div class='success'>
                <h3><i class='fas fa-check-circle'></i> Email envoyé avec succès !</h3>
                <p><strong>Destinataire :</strong> $testEmail</p>
                <p><strong>Type :</strong> Email de bienvenue</p>
                <hr>
                <p><i class='fas fa-info-circle'></i> Vérifiez votre boîte de réception (et les spams) !</p>
            </div>";
        } else {
            echo "<div class='error'>
                <h3><i class='fas fa-times-circle'></i> Erreur lors de l'envoi</h3>
                <p>L'email n'a pas pu être envoyé. Vérifiez :</p>
                <ul>
                    <li>Vos identifiants Gmail dans MailManager.php</li>
                    <li>Que le mot de passe d'application est correct</li>
                    <li>Que la validation en deux étapes est activée</li>
                </ul>
            </div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>
            <h3><i class='fas fa-exclamation-triangle'></i> Erreur</h3>
            <p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        </div>";
    }

    echo "</div>";
}

echo "
        <form method='POST' class='mt-4'>
            <div class='mb-3'>
                <label for='testEmail' class='form-label'>Email de test</label>
                <input type='email' class='form-control' id='testEmail' name='testEmail' value='$testEmail' required>
            </div>
            <button type='submit' class='btn btn-primary w-100'>
                <i class='fas fa-paper-plane'></i> Envoyer un email de test
            </button>
        </form>
        
        <div class='text-center mt-3'>
            <a href='configure_gmail.php' class='btn btn-link'>
                <i class='fas fa-cog'></i> Modifier la configuration
            </a>
        </div>
    </div>
</body>
</html>";
?>