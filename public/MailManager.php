<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * Gestionnaire d'emails avec Gmail SMTP (version simplifiée)
 */

// Charger PHPMailer directement




class MailManager
{
    private $mailer;
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;

    public function __construct()
    {
        $this->smtpPassword = app_env('SMTP_PASSWORD', '');
        $this->smtpUsername = app_env('SMTP_USERNAME', '');
        $this->smtpPort = (int) app_env('SMTP_PORT', '587');
        $this->smtpHost = app_env('SMTP_HOST', 'smtp.gmail.com');
        $this->fromName = app_env('SMTP_FROM_NAME', 'GreenTechHub');
        $this->fromEmail = app_env('SMTP_FROM_EMAIL', '');
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP()
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->smtpHost;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->smtpUsername;
        $this->mailer->Password = $this->smtpPassword;
        $this->mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $this->smtpPort;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom($this->fromEmail, $this->fromName);
    }

    /**
     * Envoyer un email de bienvenue
     */
    public function sendWelcomeEmail($email, $nom, $prenom)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, "$prenom $nom");

            $this->mailer->isHTML(true);
            $this->mailer->Subject = '🌿 Bienvenue sur GreenTechHub !';

            $htmlBody = $this->getWelcomeEmailTemplate($nom, $prenom);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi email bienvenue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template email de bienvenue
     */
    private function getWelcomeEmailTemplate($nom, $prenom)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🌿 Bienvenue sur GreenTechHub !</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $prenom $nom,</h2>
                    <p>Nous sommes ravis de vous accueillir dans notre communauté dédiée à l'innovation verte et durable !</p>
                    
                    <p><strong>Votre compte a été créé avec succès.</strong></p>
                    
                    <p>Vous pouvez maintenant :</p>
                    <ul>
                        <li>✅ Partager vos innovations écologiques</li>
                        <li>✅ Découvrir les projets de la communauté</li>
                        <li>✅ Collaborer avec d'autres innovateurs</li>
                        <li>✅ Contribuer à un avenir plus vert</li>
                    </ul>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost:8080/index.html' class='button'>Accéder à la plateforme</a>
                    </div>
                    
                    <p style='margin-top: 30px;'><strong>💡 Conseil de sécurité :</strong> Nous vous recommandons d'activer l'authentification à deux facteurs (2FA) pour sécuriser votre compte.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 GreenTechHub - Ensemble pour un avenir durable</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Envoyer un code de déverrouillage
     */
    public function sendUnlockCode($email, $nom, $prenom, $code)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, "$prenom $nom");

            $this->mailer->isHTML(true);
            $this->mailer->Subject = '🔒 Code de déverrouillage de votre compte';

            $htmlBody = $this->getUnlockCodeTemplate($nom, $prenom, $code);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi code déverrouillage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template email code déverrouillage
     */
    private function getUnlockCodeTemplate($nom, $prenom, $code)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code-box { background: #fee2e2; border: 2px dashed #ef4444; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 5px; margin: 20px 0; border-radius: 5px; color: #b91c1c; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Sécurité du compte</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $prenom $nom,</h2>
                    <p>Nous avons détecté plusieurs tentatives de connexion infructueuses sur votre compte.</p>
                    <p>Par mesure de sécurité, nous avons temporairement verrouillé votre compte. Pour le déverrouiller, veuillez utiliser le code ci-dessous :</p>
                    
                    <div class='code-box'>$code</div>
                    
                    <p>Ce code est valide pendant 10 minutes.</p>
                    <p>Si vous n'avez pas essayé de vous connecter récemment, nous vous conseillons de changer votre mot de passe immédiatement.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 GreenTechHub - Sécurité</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Send reclamation created confirmation email
     */
    public function sendReclamationCreatedEmail($email, $nom, $reclamationId, $subject)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $nom);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = "✓ Votre réclamation #$reclamationId a été enregistrée";

            $htmlBody = $this->getReclamationCreatedTemplate($nom, $reclamationId, $subject);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi email réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send admin response notification email
     */
    public function sendAdminResponseEmail($email, $nom, $reclamationId, $subject, $response)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $nom);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = "💬 Nouvelle réponse à votre réclamation #$reclamationId";

            $htmlBody = $this->getAdminResponseTemplate($nom, $reclamationId, $subject, $response);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);

            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi email réponse admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template email réclamation créée
     */
    private function getReclamationCreatedTemplate($nom, $reclamationId, $subject)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Réclamation Enregistrée</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $nom,</h2>
                    <p>Votre réclamation a été enregistrée avec succès.</p>
                    
                    <div class='info-box'>
                        <p><strong>Numéro:</strong> #$reclamationId</p>
                        <p><strong>Sujet:</strong> $subject</p>
                    </div>
                    
                    <p>Notre équipe examinera votre réclamation rapidement. Vous recevrez un email dès qu'un administrateur répondra.</p>
                    
                    <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe GreenTechHub</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2024 GreenTechHub - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template email réponse admin
     */
    private function getAdminResponseTemplate($nom, $reclamationId, $subject, $response)
    {
        $response = htmlspecialchars($response);
        $response = nl2br($response);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .response-box { background: white; padding: 20px; border: 2px solid #667eea; margin: 20px 0; border-radius: 5px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💬 Nouvelle Réponse</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $nom,</h2>
                    <p>Un administrateur a répondu à votre réclamation #$reclamationId:</p>
                    
                    <p><strong>$subject</strong></p>
                    
                    <div class='response-box'>
                        <p style='color: #667eea; font-weight: bold;'>Réponse:</p>
                        <p>$response</p>
                    </div>
                    
                    <p style='margin-top: 30px;'>Cordialement,<br><strong>L'équipe GreenTechHub</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2024 GreenTechHub - Tous droits réservés</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>