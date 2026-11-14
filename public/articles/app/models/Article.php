<?php
class Article {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all articles
    public function getAll() {
        $stmt = $this->pdo->prepare("SELECT * FROM articles ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single article by ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create a new article
    public function create($title, $content, $image) {
        $stmt = $this->pdo->prepare("INSERT INTO articles (title, content, image) VALUES (?, ?, ?)");
        return $stmt->execute([$title, $content, $image]);
    }

    // Update an existing article
    public function update($id, $title, $content, $image) {
        $stmt = $this->pdo->prepare("UPDATE articles SET title = ?, content = ?, image = ? WHERE id = ?");
        return $stmt->execute([$title, $content, $image, $id]);
    }

    // Delete an article
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
