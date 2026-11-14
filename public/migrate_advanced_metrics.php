<?php
/**
 * Migration pour les métriques avancées
 * Exécuter ce fichier une seule fois pour créer les tables nécessaires
 */

require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    echo "<h2>🚀 Début de la migration des métriques avancées...</h2>";
    echo "<div style='font-family: Arial; padding: 20px; background: #f5f5f5;'>";

    // Table pour les sessions utilisateurs (SANS foreign key)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_start DATETIME NOT NULL,
        session_end DATETIME NULL,
        duration_seconds INT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_type VARCHAR(50),
        browser VARCHAR(50),
        os VARCHAR(50),
        country VARCHAR(100),
        city VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_start (session_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'user_sessions' créée</p>";

    // Table pour les métriques quotidiennes
    $pdo->exec("CREATE TABLE IF NOT EXISTS daily_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_date DATE NOT NULL UNIQUE,
        total_users INT DEFAULT 0,
        new_users INT DEFAULT 0,
        active_users INT DEFAULT 0,
        banned_users INT DEFAULT 0,
        total_sessions INT DEFAULT 0,
        avg_session_duration INT DEFAULT 0,
        total_logins INT DEFAULT 0,
        failed_logins INT DEFAULT 0,
        total_innovations INT DEFAULT 0,
        emails_sent INT DEFAULT 0,
        notifications_sent INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_metric_date (metric_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'daily_metrics' créée</p>";

    // Table pour les événements utilisateurs (SANS foreign key)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        event_type VARCHAR(50) NOT NULL,
        event_category VARCHAR(50),
        event_data JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_event_type (event_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'user_events' créée</p>";

    // Table pour les statistiques d'emails (SANS foreign key)
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        email_type VARCHAR(50) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255),
        sent_at DATETIME NOT NULL,
        opened_at DATETIME NULL,
        clicked_at DATETIME NULL,
        bounced BOOLEAN DEFAULT FALSE,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_email_type (email_type),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'email_stats' créée</p>";

    // Table pour les rapports générés (SANS foreign key)
    $pdo->exec("CREATE TABLE IF NOT EXISTS generated_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type VARCHAR(50) NOT NULL,
        report_period VARCHAR(50),
        start_date DATE,
        end_date DATE,
        file_path VARCHAR(255),
        file_size INT,
        generated_by INT NULL,
        status VARCHAR(20) DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_report_type (report_type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'generated_reports' créée</p>";

    // Table pour les objectifs et KPIs
    $pdo->exec("CREATE TABLE IF NOT EXISTS kpi_targets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kpi_name VARCHAR(100) NOT NULL,
        target_value DECIMAL(10,2) NOT NULL,
        current_value DECIMAL(10,2) DEFAULT 0,
        period_type VARCHAR(20) DEFAULT 'monthly',
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_kpi_name (kpi_name),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p style='color: green;'>✅ Table 'kpi_targets' créée</p>";

    // Insérer des KPIs par défaut
    $pdo->exec("INSERT IGNORE INTO kpi_targets (kpi_name, target_value, period_type, start_date, end_date) VALUES
        ('Nouveaux utilisateurs', 100, 'monthly', CURDATE(), LAST_DAY(CURDATE())),
        ('Taux d\\'engagement', 75, 'monthly', CURDATE(), LAST_DAY(CURDATE())),
        ('Taux de rétention', 80, 'monthly', CURDATE(), LAST_DAY(CURDATE())),
        ('Innovations postées', 50, 'monthly', CURDATE(), LAST_DAY(CURDATE()))
    ");
    echo "<p style='color: green;'>✅ KPIs par défaut insérés</p>";

    // Créer le dossier reports s'il n'existe pas
    if (!file_exists('reports')) {
        mkdir('reports', 0777, true);
        echo "<p style='color: green;'>✅ Dossier 'reports' créé</p>";
    }

    echo "<h3 style='color: #28a745;'>✨ Migration terminée avec succès !</h3>";
    echo "<p><strong>🎯 Vous pouvez maintenant accéder à l'interface des métriques avancées.</strong></p>";
    echo "<p><a href='admin.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Accéder au Back-Office</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Erreur lors de la migration</h3>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Solution :</strong> Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>XAMPP est démarré (Apache + MySQL)</li>";
    echo "<li>La base de données 'gestion_utilisateurs' existe</li>";
    echo "<li>Les paramètres dans config.php sont corrects</li>";
    echo "</ul>";
    echo "</div>";
    exit(1);
}
?>