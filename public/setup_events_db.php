<?php
require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create evenements table
    $sqlEvents = "CREATE TABLE IF NOT EXISTS evenements (
        id_event INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        description TEXT,
        categorie VARCHAR(100),
        date_event DATE NOT NULL,
        heure_debut TIME,
        heure_fin TIME,
        lieu VARCHAR(255),
        prix DECIMAL(10, 2) DEFAULT 0.00,
        places_total INT DEFAULT 0,
        places_restantes INT DEFAULT 0,
        profit DECIMAL(10, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sqlEvents);
    echo "Table 'evenements' created or already exists.<br>";

    // Create reservations table
    $sqlReservations = "CREATE TABLE IF NOT EXISTS reservations (
        id_reserv INT AUTO_INCREMENT PRIMARY KEY,
        id_event INT NOT NULL,
        quantite INT DEFAULT 1,
        nom_utilisateur VARCHAR(255) NOT NULL,
        email_utilisateur VARCHAR(255) NOT NULL,
        statut VARCHAR(50) DEFAULT 'en_attente',
        date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_event) REFERENCES evenements(id_event) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sqlReservations);
    echo "Table 'reservations' created or already exists.<br>";

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
