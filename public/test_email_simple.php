<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * Test d'envoi d'email ULTRA SIMPLE
 */

// Charger PHPMailer




use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Email Simple</title>
    <style>
        body { font-family: Arial; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; border-radius: 20px; padding: 40px; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .success { background: #d4edda; border: 2px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .error { background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn:hover { opacity: 0.9; }
        input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; margin: 10px 0; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 style='text-align: center; color: #667eea;'>📧 Test d'envoi d'email</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['testEmail'] ?? app_env('SMTP_USERNAME');

    try {
        $mail = new PHPMailer(true);

        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = app_env('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = app_env('SMTP_USERNAME');
        $mail->Password = app_env('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) app_env('SMTP_PORT', '587');
        $mail->CharSet = 'UTF-8';

        // Destinataire
        $mail->setFrom(app_env('SMTP_FROM_EMAIL'), app_env('SMTP_FROM_NAME', 'GreenTechHub'));
        $mail->addAddress($testEmail);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = '🌿 Test - Bienvenue sur GreenTechHub !';
        $mail->Body = "
            <div style='font-family: Arial; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;'>
                <h1>🌿 Bienvenue sur GreenTechHub !</h1>
                <p>Ceci est un email de test.</p>
                <p><strong>Si vous recevez cet email, la configuration fonctionne parfaitement ! ✅</strong></p>
            </div>
        ";

        $mail->send();

        echo "<div class='success'>
            <h2>✅ Email envoyé avec succès !</h2>
            <p><strong>Destinataire :</strong> $testEmail</p>
            <p><strong>Vérifiez votre boîte de réception (et les spams) !</strong></p>
        </div>";

    } catch (Exception $e) {
        echo "<div class='error'>
            <h2>❌ Erreur lors de l'envoi</h2>
            <p><strong>Message :</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>
            <p><strong>Exception :</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        </div>";
    }
}

echo "
        <form method='POST'>
            <label><strong>Email de test :</strong></label>
            <input type='email' name='testEmail' value=app_env('SMTP_USERNAME') required>
            <button type='submit' class='btn'>📧 Envoyer un email de test</button>
        </form>
    </div>
</body>
</html>";
?>