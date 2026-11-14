<?php
require_once __DIR__ . '/../models/Article.php';

class ArticleController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo; // Assign PDO object
    }

    // List all articles
    public function list() {
        $stmt = $this->pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single article by ID
    public function get($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // returns one article
    }

    // Create a new article
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO articles (title, content, image, user_id, user_name) VALUES (:title, :content, :image, :user_id, :user_name)");
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':image' => $data['image'],
            ':user_id' => $data['user_id'] ?? null,
            ':user_name' => $data['user_name'] ?? 'Anonyme'
        ]);
    }

    // Update an article
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE articles SET title=:title, content=:content, image=:image WHERE id=:id");
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':image' => $data['image'],
            ':id' => $id
        ]);
    }

    // Delete an article
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM articles WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    // Like an article
    public function like($articleId) {
        // Check if article exists and get current likes
        $stmt = $this->pdo->prepare("SELECT likes FROM articles WHERE id = :id");
        $stmt->execute(['id' => $articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($article) {
            $newLikes = $article['likes'] + 1;
            $update = $this->pdo->prepare("UPDATE articles SET likes = :likes WHERE id = :id");
            $update->execute([
                'likes' => $newLikes,
                'id' => $articleId
            ]);
            return $newLikes;
        }
        return 0;
    }

    // Unlike an article
    public function unlike($articleId) {
        // Check if article exists and get current likes
        $stmt = $this->pdo->prepare("SELECT likes FROM articles WHERE id = :id");
        $stmt->execute(['id' => $articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($article) {
            $newLikes = max(0, $article['likes'] - 1); // Don't go below 0
            $update = $this->pdo->prepare("UPDATE articles SET likes = :likes WHERE id = :id");
            $update->execute([
                'likes' => $newLikes,
                'id' => $articleId
            ]);
            return $newLikes;
        }
        return 0;
    }

    // Get current likes count for an article
    public function getLikes($articleId) {
        $stmt = $this->pdo->prepare("SELECT likes FROM articles WHERE id = :id");
        $stmt->execute(['id' => $articleId]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        return $article ? $article['likes'] : 0;
    }

    // Search articles by title or content
    public function search($query) {
        $search = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.name as category, c.icon as category_icon 
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.title LIKE :search OR a.content LIKE :search
            ORDER BY a.created_at DESC
        ");
        $stmt->execute(['search' => $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get related articles by category
    public function getRelated($articleId, $limit = 3) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.name as category, c.icon as category_icon 
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.category_id = (SELECT category_id FROM articles WHERE id = :id)
            AND a.id != :id
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':id', $articleId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get articles sorted by likes
    public function getByMostLiked($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.name as category, c.icon as category_icon 
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            ORDER BY a.likes DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get articles sorted by comments count
    public function getByMostCommented($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.name as category, c.icon as category_icon, 
            COUNT(cm.id) as comment_count
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN comments cm ON a.id = cm.article_id
            GROUP BY a.id
            ORDER BY comment_count DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get articles with sorting and filtering
    public function listWithSort($sortBy = 'recent', $category = null) {
        $query = "
            SELECT a.*, c.name as category, c.icon as category_icon 
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
        ";

        if ($category) {
            $query .= " WHERE a.category_id = :category";
        }

        switch ($sortBy) {
            case 'popular':
                $query .= " ORDER BY a.likes DESC";
                break;
            case 'commented':
                $query .= " ORDER BY (SELECT COUNT(*) FROM comments WHERE article_id = a.id) DESC";
                break;
            case 'oldest':
                $query .= " ORDER BY a.created_at ASC";
                break;
            default: // 'recent'
                $query .= " ORDER BY a.created_at DESC";
        }

        $stmt = $this->pdo->prepare($query);
        if ($category) {
            $stmt->execute(['category' => $category]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all categories
    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
