<?php
/**
 * Report API
 * Handles user reports for articles and comments
 */

require_once __DIR__ . '/../articles/app/config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $reportType = $data['report_type'] ?? null;       // 'article' or 'comment'
    $entityId = intval($data['entity_id'] ?? 0);      // article or comment ID
    $reason = trim($data['reason'] ?? '');            // report reason
    $email = !empty($data['email']) ? trim($data['email']) : null;  // reporter email (optional)
    
    // Validation
    if (!$reportType || !in_array($reportType, ['article', 'comment'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid report type']);
        exit;
    }
    
    if (!$entityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Entity ID required']);
        exit;
    }
    
    if (!$reason || strlen($reason) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Reason required']);
        exit;
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid email format required']);
        exit;
    }
    
    // Check if entity exists
    if ($reportType === 'article') {
        $checkStmt = $pdo->prepare("SELECT id FROM articles WHERE id = ?");
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
    }
    
    $checkStmt->execute([$entityId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Entity not found']);
        exit;
    }
    
    // Check for duplicate reports (only if email provided)
    if ($email) {
        $duplicateStmt = $pdo->prepare("
            SELECT id FROM reports 
            WHERE reported_by = ? 
            AND report_type = ? 
            AND " . ($reportType === 'article' ? 'article_id' : 'comment_id') . " = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        $duplicateStmt->execute([$email, $reportType, $entityId]);
        
        if ($duplicateStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'You already reported this within 24 hours']);
            exit;
        }
    }
    
    // Insert report
    $insertStmt = $pdo->prepare("
        INSERT INTO reports (
            report_type,
            article_id,
            comment_id,
            reason,
            reported_by,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $articleId = ($reportType === 'article') ? $entityId : null;
    $commentId = ($reportType === 'comment') ? $entityId : null;
    
    $insertStmt->execute([
        $reportType,
        $articleId,
        $commentId,
        $reason,
        $email
    ]);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => '✅ Merci de votre signalement. Nos modérateurs examineront le contenu.',
        'report_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
