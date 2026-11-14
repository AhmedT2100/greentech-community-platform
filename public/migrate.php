<?php
include 'config.php';

echo "<h1>Database Migration</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM utilisateur LIKE 'is_banned'");
    if ($check->rowCount() == 0) {
        // Add column
        $sql = "ALTER TABLE utilisateur ADD COLUMN is_banned TINYINT(1) DEFAULT 0";
        $db->exec($sql);
        echo "<p style='color:green'>Column 'is_banned' added successfully.</p>";
    } else {
        echo "<p style='color:blue'>Column 'is_banned' already exists.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>