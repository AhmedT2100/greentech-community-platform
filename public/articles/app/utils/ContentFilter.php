<?php
/**
 * ContentFilter - Utility class for filtering bad words in content
 */

class ContentFilter {
    private $pdo;
    private $badWords = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadBadWords();
    }

    /**
     * Load bad words from database
     */
    private function loadBadWords() {
        try {
            $stmt = $this->pdo->query("SELECT word FROM bad_words");
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->badWords = array_map('strtolower', $results);
        } catch (Exception $e) {
            // Table doesn't exist yet, use empty array
            $this->badWords = [];
        }
    }

    /**
     * Filter content - hide bad words completely
     * @param string $content The content to filter
     * @return string Filtered content with bad words hidden
     */
    public function filterContent($content) {
        if (empty($this->badWords)) {
            return $content;
        }

        $filtered = $content;
        foreach ($this->badWords as $word) {
            // Case-insensitive replacement with [inappropriate]
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $filtered = preg_replace($pattern, '[inappropriate]', $filtered);
        }

        return $filtered;
    }

    /**
     * Blur bad words with asterisks
     * @param string $content The content to filter
     * @return string Filtered content with bad words blurred
     */
    public function blurContent($content) {
        if (empty($this->badWords)) {
            return $content;
        }

        $blurred = $content;
        foreach ($this->badWords as $word) {
            $asterisks = str_repeat('*', strlen($word));
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $blurred = preg_replace($pattern, $asterisks, $blurred);
        }

        return $blurred;
    }

    /**
     * Replace bad words with emojis
     * @param string $content The content to filter
     * @return string Filtered content with bad words replaced with emoji
     */
    public function replaceWithEmoji($content) {
        if (empty($this->badWords)) {
            return $content;
        }

        $emoji_replaced = $content;
        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $emoji_replaced = preg_replace($pattern, '😶', $emoji_replaced);
        }

        return $emoji_replaced;
    }

    /**
     * Check if content contains bad words
     * @param string $content The content to check
     * @return bool True if bad words found
     */
    public function containsBadWords($content) {
        if (empty($this->badWords)) {
            return false;
        }

        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detected bad words
     * @param string $content The content to analyze
     * @return array Array of detected bad words
     */
    public function getDetectedBadWords($content) {
        $detected = [];

        if (empty($this->badWords)) {
            return $detected;
        }

        foreach ($this->badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $detected[] = $word;
            }
        }

        return $detected;
    }

    /**
     * Add a new bad word to database
     * @param string $word The word to add
     * @param string $language Language ('fr', 'en', or 'both')
     * @return bool Success
     */
    public function addBadWord($word, $language = 'both') {
        try {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO bad_words (word, language) VALUES (?, ?)");
            $result = $stmt->execute([strtolower($word), $language]);
            if ($result) {
                $this->loadBadWords(); // Reload bad words list
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove a bad word from database
     * @param string $word The word to remove
     * @return bool Success
     */
    public function removeBadWord($word) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM bad_words WHERE word = ?");
            $result = $stmt->execute([strtolower($word)]);
            if ($result) {
                $this->loadBadWords(); // Reload bad words list
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
