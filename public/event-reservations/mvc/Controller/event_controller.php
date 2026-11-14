<?php
// mvc/Controller/event_controller.php
// Simple controller that exposes API endpoints and view includes for Event CRUD

include_once __DIR__ . '/../Config/config.php';
include_once __DIR__ . '/../Model/Event.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
$eventModel = new Event($db);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// API endpoints (return JSON)
if (strpos($action, 'api_') === 0) {
    $response = ['success' => false, 'message' => ''];
    try {
        switch($action) {
            case 'api_list':
                $events = $eventModel->getAll();
                // Ensure profit is always a number
                foreach ($events as &$event) {
                    $event['profit'] = isset($event['profit']) ? (float)$event['profit'] : 0.0;
                }
                $response['success'] = true;
                $response['events'] = $events;
                break;

            case 'api_get':
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $event = $eventModel->getById($id);
                    if ($event) { $response['success'] = true; $response['event'] = $event; }
                    else { $response['message'] = 'Événement non trouvé'; }
                } else { $response['message'] = 'ID manquant'; }
                break;

            case 'api_create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = [
                        'titre' => $_POST['titre'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'categorie' => $_POST['categorie'] ?? '',
                        'date_event' => $_POST['date_event'] ?? '',
                        'heure_debut' => $_POST['heure_debut'] ?? '',
                        'heure_fin' => $_POST['heure_fin'] ?? '',
                        'lieu' => $_POST['lieu'] ?? '',
                        'prix' => $_POST['prix'] ?? 0,
                        'places_total' => $_POST['places_total'] ?? 0
                    ];
                    if (!empty($data['titre']) && !empty($data['date_event'])) {
                        $ok = $eventModel->create($data);
                        $response['success'] = (bool)$ok;
                        $response['message'] = $ok ? 'Événement créé' : 'Erreur création';
                    } else {
                        $response['message'] = 'Titre et date requis';
                    }
                } else {
                    $response['message'] = 'Méthode non autorisée';
                }
                break;

            case 'api_update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id = $_POST['id'] ?? null;
                    if ($id) {
                        $data = [
                            'titre' => $_POST['titre'] ?? '',
                            'description' => $_POST['description'] ?? '',
                            'categorie' => $_POST['categorie'] ?? '',
                            'date_event' => $_POST['date_event'] ?? '',
                            'heure_debut' => $_POST['heure_debut'] ?? '',
                            'heure_fin' => $_POST['heure_fin'] ?? '',
                            'lieu' => $_POST['lieu'] ?? '',
                            'prix' => $_POST['prix'] ?? 0,
                            'places_total' => $_POST['places_total'] ?? 0,
                            'places_restantes' => $_POST['places_restantes'] ?? 0
                        ];
                        $ok = $eventModel->update($id, $data);
                        $response['success'] = (bool)$ok;
                        $response['message'] = $ok ? 'Événement mis à jour' : 'Erreur mise à jour';
                    } else {
                        $response['message'] = 'ID manquant';
                    }
                } else {
                    $response['message'] = 'Méthode non autorisée';
                }
                break;

            case 'api_delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $id = $_POST['id'] ?? null;
                    if ($id) {
                        $ok = $eventModel->delete($id);
                        $response['success'] = (bool)$ok;
                        $response['message'] = $ok ? 'Événement supprimé' : 'Erreur suppression';
                    } else {
                        $response['message'] = 'ID manquant';
                    }
                } else {
                    $response['message'] = 'Méthode non autorisée';
                }
                break;

            default:
                $response['message'] = 'Action API non reconnue';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Simple view routing: include the requested view file (non-API)
switch($action) {
    case 'view_create':
        include __DIR__ . '/../View/create_event.php';
        break;
    case 'view_read':
        include __DIR__ . '/../View/read_event.php';
        break;
    case 'view_update':
        include __DIR__ . '/../View/update_event.php';
        break;
    case 'view_delete':
        include __DIR__ . '/../View/delete_event.php';
        break;
    default:
        // default: show list view if exists, otherwise output simple message
        if (file_exists(__DIR__ . '/../View/read_event.php')) {
            include __DIR__ . '/../View/read_event.php';
        } else {
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
}

?>
