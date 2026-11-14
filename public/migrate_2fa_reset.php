<?php
/**
 * Migration pour 2FA et Reset Password
 */

require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    echo "<h2>🚀 Migration 2FA & Reset Password...</h2>";
    echo "<div style='font-family: Arial; padding: 20px; background: #f5f5f5;'>";

    // Ajouter les colonnes 2FA à la table users si elles n'existent pas
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE");
        echo "<p style='color: green;'>✅ Colonne 'two_factor_enabled' ajoutée</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Colonne 'two_factor_enabled' existe déjà</p>";
    }

    // Table pour les codes 2FA
    $pdo->exec("CREATE TABLE IF NOT EXISTS two_factor_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_code (code),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'two_factor_codes' créée</p>";

    // Table pour les tokens de réinitialisation de mot de passe
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_token (token),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'password_reset_tokens' créée</p>";

    echo "<h3 style='color: #28a745;'>✨ Migration terminée avec succès !</h3>";
    echo "<p><strong>Fonctionnalités ajoutées :</strong></p>";
    echo "<ul>";
    echo "<li>✅ Authentification à deux facteurs (2FA)</li>";
    echo "<li>✅ Réinitialisation de mot de passe par email</li>";
    echo "<li>✅ Email de bienvenue automatique</li>";
    echo "</ul>";
    echo "<p><a href='index.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Retour à l'accueil</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Erreur lors de la migration</h3>";
    echo "<p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit(1);
}
?>