<?php
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../utils/ContentFilter.php';

class CommentController {
    private $commentModel;
    private $contentFilter;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->commentModel = new Comment($pdo);
        $this->contentFilter = new ContentFilter($pdo);
    }

    // List comments for an article
    public function list($article_id) {
        return $this->commentModel->getByArticle($article_id);
    }

    // Get a single comment
    public function get($id) {
        return $this->commentModel->getById($id);
    }

    // Add a comment to an article
    public function create($article_id, $comment, $user_id, $user_name, $parent_comment_id = null) {
        if (!empty(trim($comment))) {
            // Store original comment without filtering - filter only on display
            return $this->commentModel->create($article_id, $comment, $user_id, $user_name, $parent_comment_id);
        }
        return false;
    }

    // Update a comment
    public function update($id, $comment) {
        if (!empty(trim($comment))) {
            // Store original comment without filtering - filter only on display
            return $this->commentModel->update($id, $comment);
        }
        return false;
    }

    // Delete a comment
    public function delete($id) {
        return $this->commentModel->delete($id);
    }

    // Like a comment
    public function like($id) {
        return $this->commentModel->like($id);
    }

    // Unlike a comment
    public function unlike($id) {
        return $this->commentModel->unlike($id);
    }

    // Get likes for a comment
    public function getLikes($id) {
        return $this->commentModel->getLikes($id);
    }

    // Get comments by article
    public function getCommentsByArticle($article_id) {
        return $this->commentModel->getByArticle($article_id);
    }

    // Get the comment model (for advanced operations like getReplies)
    public function getCommentModel() {
        return $this->commentModel;
    }

    // Get filtered comment for frontend display
    public function getFilteredComment($id) {
        $comment = $this->commentModel->getById($id);
        if ($comment) {
            $comment['comment'] = $this->contentFilter->filterContent($comment['comment']);
        }
        return $comment;
    }

    // Filter content for display
    public function filterForDisplay($text) {
        return $this->contentFilter->filterContent($text);
    }
}
