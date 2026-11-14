<?php
/**
 * API pour les métriques avancées
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Connexion échouée");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Connexion échouée']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getDashboardMetrics':
        getDashboardMetrics($pdo);
        break;

    case 'getUserGrowth':
        getUserGrowth($pdo);
        break;

    case 'getActiveUsers':
        getActiveUsers($pdo);
        break;

    case 'getSessionStats':
        getSessionStats($pdo);
        break;

    case 'getEmailStats':
        getEmailStats($pdo);
        break;

    case 'getDeviceStats':
        getDeviceStats($pdo);
        break;

    case 'getGeolocationStats':
        getGeolocationStats($pdo);
        break;

    case 'getKPIProgress':
        getKPIProgress($pdo);
        break;

    case 'getRealtimeActivity':
        getRealtimeActivity($pdo);
        break;

    case 'exportReport':
        exportReport($pdo);
        break;

    case 'trackEvent':
        trackEvent($pdo);
        break;

    case 'startSession':
        startSession($pdo);
        break;

    case 'endSession':
        endSession($pdo);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}

/**
 * Obtenir les métriques du tableau de bord
 */
function getDashboardMetrics($pdo)
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $lastWeek = date('Y-m-d', strtotime('-7 days'));
    $lastMonth = date('Y-m-d', strtotime('-30 days'));

    // Utilisateurs totaux
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nouveaux utilisateurs aujourd'hui
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $newUsersToday = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Utilisateurs actifs (7 derniers jours)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as total FROM user_sessions WHERE session_start >= ?");
    $stmt->execute([$lastWeek]);
    $activeUsers7d = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Utilisateurs bannis
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_bans WHERE (ban_until IS NULL OR ban_until > NOW())");
    $bannedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Sessions aujourd'hui
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_sessions WHERE DATE(session_start) = ?");
    $stmt->execute([$today]);
    $sessionsToday = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Durée moyenne de session (en minutes)
    $stmt = $pdo->prepare("SELECT AVG(duration_seconds) as avg_duration FROM user_sessions WHERE session_end IS NOT NULL AND DATE(session_start) >= ?");
    $stmt->execute([$lastWeek]);
    $avgDuration = round(($stmt->fetch(PDO::FETCH_ASSOC)['avg_duration'] ?? 0) / 60, 1);

    // Échecs de connexion (24h)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_events WHERE event_type = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $failedLogins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Emails envoyés aujourd'hui
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM email_stats WHERE DATE(sent_at) = ?");
    $stmt->execute([$today]);
    $emailsSent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Taux d'ouverture des emails
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_sent,
        SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened
    FROM email_stats WHERE DATE(sent_at) >= ?");
    $stmt->execute([$lastMonth]);
    $emailData = $stmt->fetch(PDO::FETCH_ASSOC);
    $emailOpenRate = $emailData['total_sent'] > 0 ? round(($emailData['total_opened'] / $emailData['total_sent']) * 100, 1) : 0;

    // Taux de rétention (utilisateurs actifs ce mois qui étaient actifs le mois dernier)
    $stmt = $pdo->query("SELECT 
        COUNT(DISTINCT CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as current_month,
        COUNT(DISTINCT CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND session_start < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as last_month
    FROM user_sessions");
    $retentionData = $stmt->fetch(PDO::FETCH_ASSOC);
    $retentionRate = $retentionData['last_month'] > 0 ? round(($retentionData['current_month'] / $retentionData['last_month']) * 100, 1) : 0;

    echo json_encode([
        'success' => true,
        'metrics' => [
            'totalUsers' => $totalUsers,
            'newUsersToday' => $newUsersToday,
            'activeUsers7d' => $activeUsers7d,
            'bannedUsers' => $bannedUsers,
            'sessionsToday' => $sessionsToday,
            'avgSessionDuration' => $avgDuration,
            'failedLogins24h' => $failedLogins,
            'emailsSentToday' => $emailsSent,
            'emailOpenRate' => $emailOpenRate,
            'retentionRate' => $retentionRate
        ]
    ]);
}

/**
 * Obtenir la croissance des utilisateurs
 */
function getUserGrowth($pdo)
{
    $days = $_GET['days'] ?? 30;

    $stmt = $pdo->prepare("SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_users,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) <= DATE(u.created_at)) as cumulative_users
    FROM users u
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC");
    $stmt->execute([$days]);
    $growth = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $growth]);
}

/**
 * Obtenir les utilisateurs actifs par période
 */
function getActiveUsers($pdo)
{
    $stmt = $pdo->query("SELECT 
        COUNT(DISTINCT CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN user_id END) as daily,
        COUNT(DISTINCT CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN user_id END) as weekly,
        COUNT(DISTINCT CASE WHEN session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as monthly
    FROM user_sessions");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Obtenir les statistiques de sessions
 */
function getSessionStats($pdo)
{
    $days = $_GET['days'] ?? 7;

    $stmt = $pdo->prepare("SELECT 
        DATE(session_start) as date,
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as unique_users,
        AVG(duration_seconds) as avg_duration,
        MAX(duration_seconds) as max_duration
    FROM user_sessions
    WHERE session_start >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(session_start)
    ORDER BY date ASC");
    $stmt->execute([$days]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Obtenir les statistiques d'emails
 */
function getEmailStats($pdo)
{
    $stmt = $pdo->query("SELECT 
        email_type,
        COUNT(*) as total_sent,
        SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
        SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as total_clicked,
        SUM(CASE WHEN bounced = 1 THEN 1 ELSE 0 END) as total_bounced,
        ROUND(SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as open_rate,
        ROUND(SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as click_rate
    FROM email_stats
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY email_type");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Obtenir les statistiques par appareil
 */
function getDeviceStats($pdo)
{
    $stmt = $pdo->query("SELECT 
        device_type,
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as unique_users,
        AVG(duration_seconds) as avg_duration
    FROM user_sessions
    WHERE session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY device_type
    ORDER BY total_sessions DESC");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Obtenir les statistiques de géolocalisation
 */
function getGeolocationStats($pdo)
{
    $stmt = $pdo->query("SELECT 
        country,
        city,
        COUNT(*) as total_sessions,
        COUNT(DISTINCT user_id) as unique_users
    FROM user_sessions
    WHERE session_start >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND country IS NOT NULL
    GROUP BY country, city
    ORDER BY total_sessions DESC
    LIMIT 20");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Obtenir la progression des KPIs
 */
function getKPIProgress($pdo)
{
    $stmt = $pdo->query("SELECT * FROM kpi_targets WHERE status = 'active' ORDER BY created_at DESC");
    $kpis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer les valeurs actuelles
    foreach ($kpis as &$kpi) {
        switch ($kpi['kpi_name']) {
            case 'Nouveaux utilisateurs':
                $stmt = $pdo->prepare("SELECT COUNT(*) as value FROM users WHERE created_at >= ? AND created_at <= ?");
                $stmt->execute([$kpi['start_date'], $kpi['end_date']]);
                $kpi['current_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['value'];
                break;

            case 'Taux d\'engagement':
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as active FROM user_sessions WHERE session_start >= ? AND session_start <= ?");
                $stmt->execute([$kpi['start_date'], $kpi['end_date']]);
                $active = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                $kpi['current_value'] = $total > 0 ? round(($active / $total) * 100, 1) : 0;
                break;
        }

        $kpi['progress'] = $kpi['target_value'] > 0 ? round(($kpi['current_value'] / $kpi['target_value']) * 100, 1) : 0;
    }

    echo json_encode(['success' => true, 'data' => $kpis]);
}

/**
 * Obtenir l'activité en temps réel
 */
function getRealtimeActivity($pdo)
{
    // Derniers événements (5 minutes)
    $stmt = $pdo->query("SELECT 
        e.event_type,
        e.event_category,
        e.created_at,
        u.nom,
        u.prenom
    FROM user_events e
    LEFT JOIN users u ON e.user_id = u.id
    WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ORDER BY e.created_at DESC
    LIMIT 20");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Utilisateurs en ligne (sessions actives dans les 5 dernières minutes)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE session_start >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND (session_end IS NULL OR session_end >= DATE_SUB(NOW(), INTERVAL 5 MINUTE))");
    $onlineUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'data' => [
            'events' => $events,
            'onlineUsers' => $onlineUsers
        ]
    ]);
}

/**
 * Exporter un rapport
 */
function exportReport($pdo)
{
    $reportType = $_POST['reportType'] ?? 'general';
    $startDate = $_POST['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_POST['endDate'] ?? date('Y-m-d');

    // Générer le CSV
    $filename = "report_{$reportType}_" . date('Y-m-d_His') . ".csv";
    $filepath = "reports/" . $filename;

    // Créer le dossier reports s'il n'existe pas
    if (!file_exists('reports')) {
        mkdir('reports', 0777, true);
    }

    $file = fopen($filepath, 'w');

    // En-têtes CSV
    fputcsv($file, ['Date', 'Utilisateurs Totaux', 'Nouveaux Utilisateurs', 'Utilisateurs Actifs', 'Sessions', 'Durée Moyenne (min)']);

    // Données
    $stmt = $pdo->prepare("SELECT 
        metric_date,
        total_users,
        new_users,
        active_users,
        total_sessions,
        avg_session_duration
    FROM daily_metrics
    WHERE metric_date >= ? AND metric_date <= ?
    ORDER BY metric_date ASC");
    $stmt->execute([$startDate, $endDate]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($file, [
            $row['metric_date'],
            $row['total_users'],
            $row['new_users'],
            $row['active_users'],
            $row['total_sessions'],
            round($row['avg_session_duration'] / 60, 1)
        ]);
    }

    fclose($file);

    // Enregistrer dans la base de données
    $fileSize = filesize($filepath);
    $stmt = $pdo->prepare("INSERT INTO generated_reports (report_type, report_period, start_date, end_date, file_path, file_size) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$reportType, 'custom', $startDate, $endDate, $filepath, $fileSize]);

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'downloadUrl' => $filepath
    ]);
}

/**
 * Suivre un événement utilisateur
 */
function trackEvent($pdo)
{
    $userId = $_POST['userId'] ?? null;
    $eventType = $_POST['eventType'] ?? '';
    $eventCategory = $_POST['eventCategory'] ?? '';
    $eventData = $_POST['eventData'] ?? '{}';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO user_events (user_id, event_type, event_category, event_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $eventType, $eventCategory, $eventData, $ipAddress, $userAgent]);

    echo json_encode(['success' => true, 'eventId' => $pdo->lastInsertId()]);
}

/**
 * Démarrer une session
 */
function startSession($pdo)
{
    $userId = $_POST['userId'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Détecter le type d'appareil
    $deviceType = detectDeviceType($userAgent);
    $browser = detectBrowser($userAgent);
    $os = detectOS($userAgent);

    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_start, ip_address, user_agent, device_type, browser, os) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $ipAddress, $userAgent, $deviceType, $browser, $os]);

    echo json_encode(['success' => true, 'sessionId' => $pdo->lastInsertId()]);
}

/**
 * Terminer une session
 */
function endSession($pdo)
{
    $sessionId = $_POST['sessionId'] ?? null;

    $stmt = $pdo->prepare("UPDATE user_sessions SET session_end = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, session_start, NOW()) WHERE id = ?");
    $stmt->execute([$sessionId]);

    echo json_encode(['success' => true]);
}

/**
 * Détecter le type d'appareil
 */
function detectDeviceType($userAgent)
{
    if (preg_match('/mobile/i', $userAgent))
        return 'Mobile';
    if (preg_match('/tablet/i', $userAgent))
        return 'Tablet';
    return 'Desktop';
}

/**
 * Détecter le navigateur
 */
function detectBrowser($userAgent)
{
    if (preg_match('/Firefox/i', $userAgent))
        return 'Firefox';
    if (preg_match('/Chrome/i', $userAgent))
        return 'Chrome';
    if (preg_match('/Safari/i', $userAgent))
        return 'Safari';
    if (preg_match('/Edge/i', $userAgent))
        return 'Edge';
    if (preg_match('/Opera/i', $userAgent))
        return 'Opera';
    return 'Unknown';
}

/**
 * Détecter le système d'exploitation
 */
function detectOS($userAgent)
{
    if (preg_match('/Windows/i', $userAgent))
        return 'Windows';
    if (preg_match('/Mac/i', $userAgent))
        return 'MacOS';
    if (preg_match('/Linux/i', $userAgent))
        return 'Linux';
    if (preg_match('/Android/i', $userAgent))
        return 'Android';
    if (preg_match('/iOS/i', $userAgent))
        return 'iOS';
    return 'Unknown';
}
?>