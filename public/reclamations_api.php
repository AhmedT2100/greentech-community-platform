<?php
// reclamations_api.php - API pour la gestion des réclamations

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the main config
include_once 'config.php';

// Include MailManager for emails (same as auth.php)
require_once 'MailManager.php';

$database = new Database();
$db = $database->getConnection();

$response = array('success' => false, 'message' => '');
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Test connection
if (!$db) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'get_user_reclamations':
            // Get all reclamations for a specific user
            $user_id = $_GET['user_id'] ?? null;
            
            if (!$user_id) {
                $response['message'] = 'User ID required';
                break;
            }

            $query = "SELECT r.*, 
                      (SELECT rr.response_text FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_text,
                      (SELECT rr.response_date FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_date,
                      (SELECT u.nom FROM utilisateur u 
                       WHERE u.id_user = (
                           SELECT rr.responder_id FROM reclamation_response rr 
                           WHERE rr.reclamation_id = r.id_reclamation 
                           ORDER BY rr.id_response DESC LIMIT 1
                       ) LIMIT 1) AS responder_name
                      FROM reclamation r
                      WHERE r.user_id = :user_id
                      ORDER BY r.date_creation DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['reclamations'] = $reclamations;
            break;

        case 'create_reclamation':
            // Create a new reclamation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $user_id = $_POST['user_id'] ?? null;
                $sujet = $_POST['sujet'] ?? '';
                $description = $_POST['description'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $mobile_phone = $_POST['mobile_phone'] ?? '';
                $priority = $_POST['priority'] ?? 'normal';

                if (!$user_id || !$sujet || !$description) {
                    $response['message'] = 'User ID, subject and description required';
                    break;
                }

                // Validate priority
                if (!in_array($priority, ['low', 'normal', 'high'])) {
                    $priority = 'normal';
                }

                $query = "INSERT INTO reclamation (user_id, sujet, description, full_name, mobile_phone, priority) 
                         VALUES (:user_id, :sujet, :description, :full_name, :mobile_phone, :priority)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':sujet', $sujet);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':mobile_phone', $mobile_phone);
                $stmt->bindParam(':priority', $priority);

                if ($stmt->execute()) {
                    $reclamationId = $db->lastInsertId();
                    $response['success'] = true;
                    $response['message'] = 'Reclamation created successfully';
                    $response['id'] = $reclamationId;
                    
                    // Send confirmation email to user
                    try {
                        // Get user email
                        $userQuery = "SELECT email, nom FROM utilisateur WHERE id_user = :user_id";
                        $userStmt = $db->prepare($userQuery);
                        $userStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $userStmt->execute();
                        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($userData && !empty($userData['email'])) {
                            $mailManager = new MailManager();
                            $mailManager->sendReclamationCreatedEmail(
                                $userData['email'],
                                $userData['nom'],
                                $reclamationId,
                                $sujet
                            );
                        }
                    } catch (Exception $e) {
                        // Log email error but don't fail the API call
                        error_log("Email sending failed for reclamation #{$reclamationId}: " . $e->getMessage());
                    }
                } else {
                    $response['message'] = 'Error creating reclamation';
                }
            }
            break;

        case 'update_reclamation':
            // Update a reclamation (only if status is 'en attente')
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $user_id = $_POST['user_id'] ?? null;
                $sujet = $_POST['sujet'] ?? '';
                $description = $_POST['description'] ?? '';
                $full_name = $_POST['full_name'] ?? '';
                $mobile_phone = $_POST['mobile_phone'] ?? '';
                $priority = $_POST['priority'] ?? 'normal';

                if (!$id || !$user_id) {
                    $response['message'] = 'ID and User ID required';
                    break;
                }

                // Check ownership and status
                $checkQuery = "SELECT user_id, statut FROM reclamation WHERE id_reclamation = :id";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $checkStmt->execute();
                $rec = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$rec) {
                    $response['message'] = 'Reclamation not found';
                    break;
                }

                if ($rec['user_id'] != $user_id) {
                    $response['message'] = 'Access denied';
                    break;
                }

                if ($rec['statut'] !== 'en attente') {
                    $response['message'] = 'Cannot edit reclamation that is not pending';
                    break;
                }

                // Validate priority
                if (!in_array($priority, ['low', 'normal', 'high'])) {
                    $priority = 'normal';
                }

                $query = "UPDATE reclamation 
                         SET sujet = :sujet, description = :description, 
                             full_name = :full_name, mobile_phone = :mobile_phone, priority = :priority
                         WHERE id_reclamation = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':sujet', $sujet);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':mobile_phone', $mobile_phone);
                $stmt->bindParam(':priority', $priority);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Reclamation updated successfully';
                } else {
                    $response['message'] = 'Error updating reclamation';
                }
            }
            break;

        case 'delete_reclamation':
            // Delete a reclamation (only if status is 'en attente')
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $user_id = $_POST['user_id'] ?? null;

                if (!$id || !$user_id) {
                    $response['message'] = 'ID and User ID required';
                    break;
                }

                // Check ownership and status
                $checkQuery = "SELECT user_id, statut FROM reclamation WHERE id_reclamation = :id";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $checkStmt->execute();
                $rec = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$rec) {
                    $response['message'] = 'Reclamation not found';
                    break;
                }

                if ($rec['user_id'] != $user_id) {
                    $response['message'] = 'Access denied';
                    break;
                }

                if ($rec['statut'] !== 'en attente') {
                    $response['message'] = 'Cannot delete reclamation that is not pending';
                    break;
                }

                $query = "DELETE FROM reclamation WHERE id_reclamation = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Reclamation deleted successfully';
                } else {
                    $response['message'] = 'Error deleting reclamation';
                }
            }
            break;

        case 'get_all_reclamations':
            // Admin: Get all reclamations with pagination and search
            $search = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = ($page - 1) * $limit;

            $whereClause = '';
            $params = [];

            if (!empty($search)) {
                $whereClause = " WHERE (r.sujet LIKE :search OR r.description LIKE :search 
                                OR u.nom LIKE :search OR r.priority LIKE :search OR r.statut LIKE :search)";
                $searchParam = '%' . $search . '%';
                $params[':search'] = $searchParam;
            }

            $query = "SELECT r.*, u.nom AS user_name, u.email AS user_email,
                      (SELECT rr.response_text FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_text,
                      (SELECT rr.response_date FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_date,
                      (SELECT u2.nom FROM utilisateur u2 
                       WHERE u2.id_user = (
                           SELECT rr.responder_id FROM reclamation_response rr 
                           WHERE rr.reclamation_id = r.id_reclamation 
                           ORDER BY rr.id_response DESC LIMIT 1
                       ) LIMIT 1) AS responder_name
                      FROM reclamation r
                      JOIN utilisateur u ON r.user_id = u.id_user"
                      . $whereClause . 
                      " ORDER BY r.date_creation DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM reclamation r JOIN utilisateur u ON r.user_id = u.id_user" . $whereClause;
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $response['success'] = true;
            $response['reclamations'] = $reclamations;
            $response['total'] = $total;
            $response['page'] = $page;
            $response['limit'] = $limit;
            break;

        case 'get_reclamation':
            // Get single reclamation details
            $id = $_GET['id'] ?? null;

            if (!$id) {
                $response['message'] = 'ID required';
                break;
            }

            $query = "SELECT r.*, u.nom AS user_name, u.email AS user_email,
                      (SELECT rr.response_text FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_text,
                      (SELECT rr.response_date FROM reclamation_response rr 
                       WHERE rr.reclamation_id = r.id_reclamation 
                       ORDER BY rr.id_response DESC LIMIT 1) AS response_date,
                      (SELECT u2.nom FROM utilisateur u2 
                       WHERE u2.id_user = (
                           SELECT rr.responder_id FROM reclamation_response rr 
                           WHERE rr.reclamation_id = r.id_reclamation 
                           ORDER BY rr.id_response DESC LIMIT 1
                       ) LIMIT 1) AS responder_name
                      FROM reclamation r
                      JOIN utilisateur u ON r.user_id = u.id_user
                      WHERE r.id_reclamation = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reclamation) {
                $response['success'] = true;
                $response['reclamation'] = $reclamation;
            } else {
                $response['message'] = 'Reclamation not found';
            }
            break;

        case 'respond_reclamation':
            // Admin: Add response to reclamation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $response_text = $_POST['response_text'] ?? '';
                $admin_id = $_POST['admin_id'] ?? null;

                if (!$id || !$response_text || !$admin_id) {
                    $response['message'] = 'ID, response text and admin ID required';
                    break;
                }

                // Check if reclamation exists
                $checkQuery = "SELECT id_reclamation FROM reclamation WHERE id_reclamation = :id";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $checkStmt->execute();
                
                if (!$checkStmt->fetch()) {
                    $response['message'] = 'Reclamation not found';
                    break;
                }

                $query = "INSERT INTO reclamation_response (reclamation_id, response_text, response_date, responder_id)
                         VALUES (:reclamation_id, :response_text, NOW(), :responder_id)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':reclamation_id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':response_text', $response_text);
                $stmt->bindParam(':responder_id', $admin_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Response added successfully';
                    
                    // Send notification email to user
                    try {
                        // Get user and reclamation details
                        $detailsQuery = "SELECT u.email, u.nom, r.sujet 
                                        FROM utilisateur u 
                                        JOIN reclamation r ON r.user_id = u.id_user 
                                        WHERE r.id_reclamation = :id";
                        $detailsStmt = $db->prepare($detailsQuery);
                        $detailsStmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $detailsStmt->execute();
                        $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($details && !empty($details['email'])) {
                            $mailManager = new MailManager();
                            $mailManager->sendAdminResponseEmail(
                                $details['email'],
                                $details['nom'],
                                $id,
                                $details['sujet'],
                                $response_text
                            );
                        }
                    } catch (Exception $e) {
                        // Log email error but don't fail the API call
                        error_log("Email sending failed for reclamation response #{$id}: " . $e->getMessage());
                    }
                } else {
                    $response['message'] = 'Error adding response';
                }
            }
            break;

        case 'change_status':
            // Admin: Change reclamation status
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? null;
                $status = $_POST['status'] ?? '';

                if (!$id || !$status) {
                    $response['message'] = 'ID and status required';
                    break;
                }

                // Validate status
                if (!in_array($status, ['en attente', 'en cours', 'résolue'])) {
                    $response['message'] = 'Invalid status';
                    break;
                }

                $query = "UPDATE reclamation SET statut = :status";
                
                // If status is 'résolue', set date_resolution
                if ($status === 'résolue') {
                    $query .= ", date_resolution = NOW()";
                }
                
                $query .= " WHERE id_reclamation = :id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Status updated successfully';
                } else {
                    $response['message'] = 'Error updating status';
                }
            }
            break;

        case 'get_stats':
            // Get priority statistics
            $query = "SELECT priority, COUNT(*) as count FROM reclamation GROUP BY priority";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals and percentages
            $total = 0;
            $counts = ['low' => 0, 'normal' => 0, 'high' => 0];
            
            foreach ($stats as $stat) {
                $priority = $stat['priority'];
                $count = (int)$stat['count'];
                if (isset($counts[$priority])) {
                    $counts[$priority] = $count;
                }
                $total += $count;
            }
            
            $percentages = [
                'low' => $total > 0 ? round(($counts['low'] / $total) * 100, 1) : 0,
                'normal' => $total > 0 ? round(($counts['normal'] / $total) * 100, 1) : 0,
                'high' => $total > 0 ? round(($counts['high'] / $total) * 100, 1) : 0
            ];

            $response['success'] = true;
            $response['stats'] = [
                'counts' => $counts,
                'percentages' => $percentages,
                'total' => $total
            ];
            break;

        case 'export_pdf':
            // Export reclamation(s) as PDF
            $id = $_GET['id'] ?? null;
            $all = $_GET['all'] ?? false;

            if ($all) {
                // Export all reclamations
                $query = "SELECT r.*, u.nom AS user_name
                         FROM reclamation r
                         JOIN utilisateur u ON r.user_id = u.id_user
                         ORDER BY r.date_creation DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $recs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Generate HTML
                ob_start();
                ?>
                <!doctype html>
                <html>
                <head>
                    <meta charset="utf-8"/>
                    <title>Rapport - Toutes les Réclamations</title>
                    <style>
                        body { font-family: DejaVu Sans, Arial, sans-serif; padding:20px; font-size:12px; color:#222; }
                        h1 { font-size:18px; margin-bottom:6px; }
                        .rec { page-break-after: always; margin-bottom:20px; border: 1px solid #ddd; padding: 15px; }
                        .meta { margin-top:8px; line-height: 1.6; }
                        strong { color: #333; }
                    </style>
                </head>
                <body>
                    <h1>Rapport — Toutes les Réclamations (<?= count($recs) ?>)</h1>
                    <?php foreach ($recs as $rec): ?>
                        <div class="rec">
                            <h2>Réclamation #<?= htmlspecialchars($rec['id_reclamation']) ?> — <?= htmlspecialchars($rec['sujet']) ?></h2>
                            <div class="meta">
                                <strong>Date:</strong> <?= htmlspecialchars($rec['date_creation']) ?><br/>
                                <strong>Statut:</strong> <?= htmlspecialchars($rec['statut']) ?><br/>
                                <strong>Priorité:</strong> <?= htmlspecialchars($rec['priority']) ?><br/>
                                <strong>Utilisateur:</strong> <?= htmlspecialchars($rec['user_name']) ?><br/>
                                <?php if (!empty($rec['full_name'])): ?><strong>Nom:</strong> <?= htmlspecialchars($rec['full_name']) ?><br/><?php endif; ?>
                                <?php if (!empty($rec['mobile_phone'])): ?><strong>Tél:</strong> <?= htmlspecialchars($rec['mobile_phone']) ?><br/><?php endif; ?>
                            </div>
                            <h3>Description</h3>
                            <div style="white-space:pre-wrap; border:1px solid #eee; padding:10px; background:#fafafa;">
                                <?= htmlspecialchars($rec['description']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </body>
                </html>
                <?php
                $html = ob_get_clean();

                header('Content-Type: application/octet-stream');
                $filename = 'reclamations-all-' . date('YmdHis') . '.html';
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $html;
                exit;
            } else if ($id) {
                // Export single reclamation
                $query = "SELECT r.*, u.nom AS user_name
                         FROM reclamation r
                         JOIN utilisateur u ON r.user_id = u.id_user
                         WHERE r.id_reclamation = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $rec = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$rec) {
                    $response['message'] = 'Reclamation not found';
                    break;
                }

                // Generate HTML
                ob_start();
                ?>
                <!doctype html>
                <html>
                <head>
                    <meta charset="utf-8"/>
                    <title>Réclamation #<?= htmlspecialchars($rec['id_reclamation']) ?></title>
                    <style>
                        body { font-family: DejaVu Sans, Arial, sans-serif; padding:20px; font-size:14px; color:#222; }
                        h1 { font-size:20px; margin-bottom:8px; }
                        .meta { margin-top:8px; line-height: 1.8; }
                        strong { color: #333; }
                    </style>
                </head>
                <body>
                    <h1>Rapport de Réclamation #<?= htmlspecialchars($rec['id_reclamation']) ?></h1>
                    <div class="meta">
                        <strong>Sujet:</strong> <?= htmlspecialchars($rec['sujet']) ?><br/>
                        <strong>Date de création:</strong> <?= htmlspecialchars($rec['date_creation']) ?><br/>
                        <strong>Statut:</strong> <?= htmlspecialchars($rec['statut']) ?><br/>
                        <strong>Priorité:</strong> <?= htmlspecialchars($rec['priority']) ?><br/>
                        <strong>Utilisateur:</strong> <?= htmlspecialchars($rec['user_name']) ?><br/>
                        <?php if (!empty($rec['full_name'])): ?><strong>Nom complet:</strong> <?= htmlspecialchars($rec['full_name']) ?><br/><?php endif; ?>
                        <?php if (!empty($rec['mobile_phone'])): ?><strong>Téléphone:</strong> <?= htmlspecialchars($rec['mobile_phone']) ?><br/><?php endif; ?>
                    </div>

                    <h2>Description</h2>
                    <div style="white-space:pre-wrap; border:1px solid #eee; padding:10px; background:#fafafa;">
                        <?= htmlspecialchars($rec['description']) ?>
                    </div>
                </body>
                </html>
                <?php
                $html = ob_get_clean();

                header('Content-Type: application/octet-stream');
                $filename = 'reclamation-' . $rec['id_reclamation'] . '.html';
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo $html;
                exit;
            } else {
                $response['message'] = 'ID or all parameter required';
            }
            break;

        default:
            $response['message'] = 'Action not recognized: ' . $action;
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'PDO Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
