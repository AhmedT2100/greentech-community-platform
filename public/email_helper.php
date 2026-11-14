<?php
require_once __DIR__ . '/../config/bootstrap.php';
// email_helper.php - Email notification helper for reclamations using PHPMailer

// Load PHPMailer




/**
 * Get configured PHPMailer instance
 */
function getMailer() {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // SMTP Configuration (same as MailManager.php)
    $mail->isSMTP();
    $mail->Host = app_env('SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = app_env('SMTP_USERNAME');
    $mail->Password = app_env('SMTP_PASSWORD');
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) app_env('SMTP_PORT', '587');
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(app_env('SMTP_FROM_EMAIL'), app_env('SMTP_FROM_NAME', 'GreenTechHub'));
    
    return $mail;
}

/**
 * Send email notification when a new reclamation is created
 */
function sendReclamationCreatedEmail($userEmail, $userName, $reclamationId, $subject) {
    if (empty($userEmail)) {
        error_log("Email notification failed: No email provided for reclamation #$reclamationId");
        return false;
    }

    try {
        $mail = getMailer();
        $mail->clearAddresses();
        $mail->addAddress($userEmail, $userName);
        
        $mail->isHTML(true);
        $mail->Subject = "Votre réclamation #$reclamationId a été enregistrée - GreenTechHub";
        
        $htmlBody = getReclamationCreatedTemplate($userName, $reclamationId, $subject);
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);
        
        $mail->send();
        
        error_log("✓ Reclamation creation email sent successfully to $userEmail for reclamation #$reclamationId");
        return true;
    } catch (Exception $e) {
        error_log("✗ Failed to send reclamation creation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification when admin responds to a reclamation
 */
function sendAdminResponseEmail($userEmail, $userName, $reclamationId, $subject, $response) {
    if (empty($userEmail)) {
        error_log("Email notification failed: No email provided for reclamation #$reclamationId response");
        return false;
    }

    try {
        $mail = getMailer();
        $mail->clearAddresses();
        $mail->addAddress($userEmail, $userName);
        
        $mail->isHTML(true);
        $mail->Subject = "Nouvelle réponse à votre réclamation #$reclamationId - GreenTechHub";
        
        $htmlBody = getAdminResponseTemplate($userName, $reclamationId, $subject, $response);
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);
        
        $mail->send();
        
        error_log("✓ Admin response email sent successfully to $userEmail for reclamation #$reclamationId");
        return true;
    } catch (Exception $e) {
        error_log("✗ Failed to send admin response email: " . $e->getMessage());
        return false;
    }
}

/**
 * HTML template for reclamation creation confirmation
 */
function getReclamationCreatedTemplate($userName, $reclamationId, $subject) {
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .info-box { background: white; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; border-radius: 5px; }
            .tip-box { background: #e7f5ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✓ Réclamation Enregistrée</h1>
            </div>
            <div class='content'>
                <h2>Bonjour $userName,</h2>
                
                <p>Votre réclamation a été enregistrée avec succès dans notre système.</p>
                
                <div class='info-box'>
                    <p style='margin: 0;'><strong>Numéro de réclamation:</strong> #$reclamationId</p>
                    <p style='margin: 10px 0 0 0;'><strong>Sujet:</strong> $subject</p>
                </div>
                
                <p>Notre équipe examinera votre réclamation dans les plus brefs délais. Vous recevrez une notification par email dès qu'un administrateur y répondra.</p>
                
                <div class='tip-box'>
                    <p style='margin: 0; font-size: 14px; color: #0c5460;'>
                        <strong>💡 Astuce:</strong> Vous pouvez suivre l'état de votre réclamation en vous connectant à votre compte GreenTechHub.
                    </p>
                </div>
                
                <p style='margin-top: 30px;'>
                    Cordialement,<br>
                    <strong>L'équipe GreenTechHub</strong>
                </p>
            </div>
            <div class='footer'>
                <p>© 2024 GreenTechHub. Tous droits réservés.</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * HTML template for admin response notification
 */
function getAdminResponseTemplate($userName, $reclamationId, $subject, $response) {
    $response = htmlspecialchars($response);
    $response = nl2br($response);
    
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .info-box { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; border-radius: 5px; }
            .response-box { background: white; padding: 20px; border: 2px solid #dee2e6; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>💬 Nouvelle Réponse</h1>
            </div>
            <div class='content'>
                <h2>Bonjour $userName,</h2>
                
                <p>Un administrateur a répondu à votre réclamation :</p>
                
                <div class='info-box'>
                    <p style='margin: 0;'><strong>Réclamation #$reclamationId</strong></p>
                    <p style='margin: 10px 0 0 0; color: #6c757d;'>$subject</p>
                </div>
                
                <div class='response-box'>
                    <p style='margin: 0 0 10px 0; font-weight: bold; color: #667eea;'>Réponse de l'administrateur:</p>
                    <p style='margin: 0; font-size: 15px;'>$response</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost/reclamations.html' class='button'>
                        Voir ma réclamation
                    </a>
                </div>
                
                <p style='margin-top: 30px;'>
                    Cordialement,<br>
                    <strong>L'équipe GreenTechHub</strong>
                </p>
            </div>
            <div class='footer'>
                <p>© 2024 GreenTechHub. Tous droits réservés.</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
