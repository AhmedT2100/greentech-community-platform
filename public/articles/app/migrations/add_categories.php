<?php
// Migration: Add Categories to Articles
// Run this once to add category support to the articles table

require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    // Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            icon VARCHAR(50),
            color VARCHAR(7),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Add category_id to articles table if it doesn't exist
    $checkColumn = $pdo->query("SHOW COLUMNS FROM articles LIKE 'category_id'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE articles 
            ADD COLUMN category_id INT DEFAULT NULL,
            ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ");
    }

    // Insert default categories
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description, icon, color) VALUES (?, ?, ?, ?)");
    
    $categories = [
        ['Recyclage', 'Articles sur le recyclage et la gestion des déchets', '♻️', '#1dbf73'],
        ['Énergie Renouvelable', 'Énergie solaire, éolienne et alternatives', '⚡', '#ffd700'],
        ['Durabilité', 'Mode de vie durable et consommation responsable', '🌱', '#228B22'],
        ['Changement Climatique', 'Actualités et solutions climatiques', '🌍', '#4169e1'],
        ['Biodiversité', 'Protection de la nature et des écosystèmes', '🦋', '#ff69b4'],
        ['Eau Propre', 'Conservation et purification de l\'eau', '💧', '#1e90ff'],
    ];

    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    echo "✅ Migration réussie: Catégories ajoutées";

} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
