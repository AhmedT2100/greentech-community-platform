<?php
class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all comments for a specific article (including replies)
    public function getByArticle($article_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM comments 
            WHERE article_id = :article_id 
            ORDER BY 
                COALESCE(parent_comment_id, id) ASC,
                parent_comment_id IS NULL DESC,
                created_at ASC
        ");
        $stmt->execute(['article_id' => $article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get replies for a specific comment
    public function getReplies($comment_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM comments 
            WHERE parent_comment_id = :parent_comment_id 
            ORDER BY created_at ASC
        ");
        $stmt->execute(['parent_comment_id' => $comment_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a single comment by ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM comments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add a new comment or reply
    public function create($article_id, $comment, $user_id, $user_name, $parent_comment_id = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO comments (article_id, parent_comment_id, comment, user_id, user_name, created_at) 
            VALUES (:article_id, :parent_comment_id, :comment, :user_id, :user_name, NOW())
        ");
        return $stmt->execute([
            'article_id' => $article_id,
            'parent_comment_id' => $parent_comment_id,
            'comment' => $comment,
            'user_id' => $user_id,
            'user_name' => $user_name
        ]);
    }

    // Update a comment (only comment text)
    public function update($id, $comment) {
        $stmt = $this->pdo->prepare("UPDATE comments SET comment = :comment, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'comment' => $comment
        ]);
    }

    // Delete a comment (also deletes its replies)
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = :id OR parent_comment_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // Like a comment
    public function like($id) {
        $stmt = $this->pdo->prepare("SELECT likes FROM comments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            $newLikes = $comment['likes'] + 1;
            $update = $this->pdo->prepare("UPDATE comments SET likes = :likes WHERE id = :id");
            $update->execute(['likes' => $newLikes, 'id' => $id]);
            return $newLikes;
        }
        return 0;
    }

    // Unlike a comment
    public function unlike($id) {
        $stmt = $this->pdo->prepare("SELECT likes FROM comments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            $newLikes = max(0, $comment['likes'] - 1);
            $update = $this->pdo->prepare("UPDATE comments SET likes = :likes WHERE id = :id");
            $update->execute(['likes' => $newLikes, 'id' => $id]);
            return $newLikes;
        }
        return 0;
    }

    // Get likes for a comment
    public function getLikes($id) {
        $stmt = $this->pdo->prepare("SELECT likes FROM comments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        return $comment ? $comment['likes'] : 0;
    }
}
