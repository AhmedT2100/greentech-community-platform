<?php
// Script to create innovation tables in greentech database
try {
    require_once __DIR__ . '/../public/config.php';
    $pdo = (new Database())->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to greentech database successfully.\n\n";
    
    // Create categorie table
    $sql1 = "CREATE TABLE IF NOT EXISTS `categorie` (
      `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
      `nom` varchar(100) NOT NULL,
      `description` text DEFAULT NULL,
      PRIMARY KEY (`id_categorie`),
      UNIQUE KEY `nom` (`nom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql1);
    echo "✓ Table 'categorie' created successfully.\n";
    
    // Create innovation table
    $sql2 = "CREATE TABLE IF NOT EXISTS `innovation` (
      `id_innovation` int(11) NOT NULL AUTO_INCREMENT,
      `titre` varchar(150) NOT NULL,
      `description` text DEFAULT NULL,
      `image` varchar(255) DEFAULT NULL,
      `id_categorie` int(11) DEFAULT NULL,
      `statut` enum('en attente','validé','rejetée') DEFAULT 'en attente',
      `rating_total` decimal(10,2) DEFAULT 0.00,
      `rating_count` int(11) DEFAULT 0,
      PRIMARY KEY (`id_innovation`),
      KEY `categorie_id` (`id_categorie`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql2);
    echo "✓ Table 'innovation' created successfully.\n";
    
    // Add foreign key constraint if not exists
    try {
        $sql3 = "ALTER TABLE `innovation` 
                 ADD CONSTRAINT `innovation_ibfk_1` 
                 FOREIGN KEY (`id_categorie`) 
                 REFERENCES `categorie` (`id_categorie`) 
                 ON DELETE SET NULL 
                 ON UPDATE CASCADE";
        $pdo->exec($sql3);
        echo "✓ Foreign key constraint added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ Foreign key constraint already exists.\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✓✓✓ All innovation tables created successfully! ✓✓✓\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
