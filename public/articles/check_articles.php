<?php
require_once __DIR__ . '/../config.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT id, title FROM articles LIMIT 10");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Articles in Database:</h2>";
    if (empty($articles)) {
        echo "<p>No articles found!</p>";
    } else {
        echo "<ul>";
        foreach ($articles as $article) {
            echo "<li><a href='article.php?id=" . $article['id'] . "'>" . htmlspecialchars($article['title']) . "</a> (ID: " . $article['id'] . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
