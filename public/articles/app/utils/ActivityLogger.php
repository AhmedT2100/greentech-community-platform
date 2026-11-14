<?php

class ActivityLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Log an admin action to the activity logs table
     */
    public function log($admin_id, $action, $entity_type, $entity_id = null, $description = '', $details = []) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $details_json = json_encode($details);
            
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (admin_id, action, entity_type, entity_id, description, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $admin_id,
                $action,
                $entity_type,
                $entity_id,
                $description,
                $details_json,
                $ip_address
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all logs for a specific admin
     */
    public function getAdminLogs($admin_id, $limit = 100) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM activity_logs
                WHERE admin_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$admin_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent logs across all admins
     */
    public function getRecentLogs($limit = 50, $filters = []) {
        try {
            $query = "
                SELECT al.*, a.username
                FROM activity_logs al
                LEFT JOIN admins a ON al.admin_id = a.id
                WHERE 1=1
            ";
            $params = [];
            
            // Filter by action if provided
            if (!empty($filters['action'])) {
                $query .= " AND al.action = ?";
                $params[] = $filters['action'];
            }
            
            // Filter by entity type if provided
            if (!empty($filters['entity_type'])) {
                $query .= " AND al.entity_type = ?";
                $params[] = $filters['entity_type'];
            }
            
            // Filter by admin if provided
            if (!empty($filters['admin_id'])) {
                $query .= " AND al.admin_id = ?";
                $params[] = $filters['admin_id'];
            }
            
            // Filter by date range if provided
            if (!empty($filters['start_date'])) {
                $query .= " AND DATE(al.created_at) >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $query .= " AND DATE(al.created_at) <= ?";
                $params[] = $filters['end_date'];
            }
            
            $query .= " ORDER BY al.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getStatistics($days = 30) {
        try {
            $stats = [];
            
            // Total actions in period
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_actions
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            $stats['total_actions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_actions'];
            
            // Actions by type
            $stmt = $this->db->prepare("
                SELECT action, COUNT(*) as count
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action
                ORDER BY count DESC
            ");
            $stmt->execute([$days]);
            $stats['actions_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Most active admins
            $stmt = $this->db->prepare("
                SELECT al.admin_id, a.username, COUNT(*) as action_count
                FROM activity_logs al
                LEFT JOIN admins a ON al.admin_id = a.id
                WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY al.admin_id
                ORDER BY action_count DESC
                LIMIT 10
            ");
            $stmt->execute([$days]);
            $stats['most_active_admins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Entity types affected
            $stmt = $this->db->prepare("
                SELECT entity_type, COUNT(*) as count
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY entity_type
                ORDER BY count DESC
            ");
            $stmt->execute([$days]);
            $stats['entities_affected'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity heatmap data for charts (actions per day)
     */
    public function getActivityHeatmap($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as count
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
