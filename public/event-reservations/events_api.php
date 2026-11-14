<?php
// events_api.php - API pour la gestion des événements

// Temporary debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the centralized MVC config
include_once __DIR__ . '/mvc/Config/config.php';

$database = new Database();
$db = $database->getConnection();

/**
 * Calculate the total profit for a specific event
 * Profit = Sum of (quantite × event_price) for all confirmed reservations
 * 
 * @param PDO $db Database connection
 * @param int $eventId Event ID
 * @return float Total profit
 */
function calculateEventProfit($db, $eventId) {
    try {
        // Get event price
        $eventQuery = "SELECT prix FROM evenements WHERE id_event = :id_event";
        $eventStmt = $db->prepare($eventQuery);
        $eventStmt->bindParam(':id_event', $eventId);
        $eventStmt->execute();
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            return 0;
        }
        
        $eventPrice = (float)$event['prix'];
        
        // Get sum of all confirmed reservations
        $reservQuery = "SELECT SUM(quantite) as total_quantity 
                       FROM reservations 
                       WHERE id_event = :id_event AND statut = 'confirmee'";
        $reservStmt = $db->prepare($reservQuery);
        $reservStmt->bindParam(':id_event', $eventId);
        $reservStmt->execute();
        $result = $reservStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalQuantity = (int)($result['total_quantity'] ?? 0);
        
        // Calculate profit
        $profit = $totalQuantity * $eventPrice;
        $profit = round($profit, 2);
        
        // UPDATE: Store profit in database
        $updateQuery = "UPDATE evenements SET profit = :profit WHERE id_event = :id_event";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':profit', $profit);
        $updateStmt->bindParam(':id_event', $eventId);
        $updateStmt->execute();
        
        return $profit;
    } catch (PDOException $e) {
        error_log("Error calculating profit: " . $e->getMessage());
        return 0;
    }
}

$response = array('success' => false, 'message' => '');
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Test connection
if (!$db) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

try {
    switch($action) {
        case 'get_all_events':
            // Récupérer tous les événements
            $query = "SELECT * FROM evenements ORDER BY date_event ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['events'] = $events;
            break;

        case 'get_events':
            // Récupérer tous les événements (status filtering removed)
            $query = "SELECT * FROM evenements ORDER BY date_event ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['events'] = $events;
            break;

        case 'add_event':
            // Ajouter un nouvel événement
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $titre = $_POST['titre'] ?? '';
                $description = $_POST['description'] ?? '';
                $categorie = $_POST['categorie'] ?? '';
                $date_event = $_POST['date_event'] ?? '';
                $heure_debut = $_POST['heure_debut'] ?? '';
                $heure_fin = $_POST['heure_fin'] ?? '';
                $lieu = $_POST['lieu'] ?? '';
                $prix = $_POST['prix'] ?? 0;
                $places_total = $_POST['places_total'] ?? 0;

                if (!empty($titre) && !empty($date_event)) {
                    $query = "INSERT INTO evenements (titre, description, categorie, date_event, heure_debut, heure_fin, lieu, prix, places_total, places_restantes) 
                             VALUES (:titre, :description, :categorie, :date_event, :heure_debut, :heure_fin, :lieu, :prix, :places_total, :places_restantes)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':titre', $titre);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':categorie', $categorie);
                    $stmt->bindParam(':date_event', $date_event);
                    $stmt->bindParam(':heure_debut', $heure_debut);
                    $stmt->bindParam(':heure_fin', $heure_fin);
                    $stmt->bindParam(':lieu', $lieu);
                    $stmt->bindParam(':prix', $prix);
                    $stmt->bindParam(':places_total', $places_total);
                    $stmt->bindParam(':places_restantes', $places_total);
                    
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Événement ajouté avec succès';
                    } else {
                        $response['message'] = 'Erreur lors de l\'ajout';
                    }
                } else {
                    $response['message'] = 'Titre et date requis';
                }
            }
            break;

        case 'reserve_event':
            // Créer une réservation (statut en_attente) — admin confirmera ensuite
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id_event = $_POST['id_event'] ?? null;
                $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 1;
                $nom = $_POST['nom_utilisateur'] ?? '';
                $email = $_POST['email_utilisateur'] ?? '';

                if (!$id_event || !$nom || !$email || $quantite < 1) {
                    $response['message'] = 'Données de réservation invalides';
                    break;
                }

                try {
                    $db->beginTransaction();

                    // Optional: verify event exists
                    $q = "SELECT id_event, places_restantes FROM evenements WHERE id_event = :id_event";
                    $s = $db->prepare($q);
                    $s->bindParam(':id_event', $id_event);
                    $s->execute();
                    $ev = $s->fetch(PDO::FETCH_ASSOC);
                    if (!$ev) {
                        $db->rollBack();
                        $response['message'] = 'Événement introuvable';
                        break;
                    }

                    // Insert reservation with 'en_attente' status
                    $ins = "INSERT INTO reservations (id_event, quantite, nom_utilisateur, email_utilisateur, statut, date_reservation) VALUES (:id_event, :quantite, :nom, :email, 'en_attente', NOW())";
                    $si = $db->prepare($ins);
                    $si->bindParam(':id_event', $id_event);
                    $si->bindParam(':quantite', $quantite);
                    $si->bindParam(':nom', $nom);
                    $si->bindParam(':email', $email);
                    $ok = $si->execute();

                    if (!$ok) {
                        $db->rollBack();
                        $response['message'] = 'Impossible de créer la réservation';
                        break;
                    }

                    $db->commit();
                    $response['success'] = true;
                    $response['message'] = 'Réservation enregistrée (en attente de confirmation)';
                } catch (PDOException $e) {
                    $db->rollBack();
                    $response['message'] = 'Erreur réservation: ' . $e->getMessage();
                }
            }
            break;

        case 'get_all_reservations':
            // Récupérer toutes les réservations
            $query = "SELECT r.*, e.titre, e.date_event, e.lieu 
                     FROM reservations r 
                     JOIN evenements e ON r.id_event = e.id_event 
                     ORDER BY r.date_reservation DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['reservations'] = $reservations;
            break;

        case 'update_reservation_status':
            // Mettre à jour le statut d'une réservation (confirmee / refusee)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id_reserv = $_POST['id_reserv'] ?? null;
                $new_status = $_POST['status'] ?? null; // expected: 'confirmee' or 'refusee'

                if (!$id_reserv || !$new_status) {
                    $response['message'] = 'id_reserv et status requis';
                    break;
                }

                // Start transaction for consistency
                $db->beginTransaction();
                try {
                    // Load reservation
                    $q = "SELECT * FROM reservations WHERE id_reserv = :id_reserv FOR UPDATE";
                    $s = $db->prepare($q);
                    $s->bindParam(':id_reserv', $id_reserv);
                    $s->execute();
                    $reserv = $s->fetch(PDO::FETCH_ASSOC);

                    if (!$reserv) {
                        $db->rollBack();
                        $response['message'] = 'Réservation introuvable';
                        break;
                    }

                    $current_status = $reserv['statut'];

                    // If already in desired status, return success
                    if ($current_status === $new_status) {
                        $db->commit();
                        $response['success'] = true;
                        $response['message'] = 'Statut déjà à jour';
                        break;
                    }

                    // If confirming, ensure enough places and decrement
                    if ($new_status === 'confirmee') {
                        // Load event with FOR UPDATE
                        $q2 = "SELECT id_event, places_restantes FROM evenements WHERE id_event = :id_event FOR UPDATE";
                        $s2 = $db->prepare($q2);
                        $s2->bindParam(':id_event', $reserv['id_event']);
                        $s2->execute();
                        $event = $s2->fetch(PDO::FETCH_ASSOC);

                        if (!$event) {
                            $db->rollBack();
                            $response['message'] = 'Événement introuvable';
                            break;
                        }

                        $places_restantes = (int)$event['places_restantes'];
                        $quantite = (int)$reserv['quantite'];

                        if ($places_restantes < $quantite) {
                            $db->rollBack();
                            $response['message'] = 'Pas assez de places disponibles';
                            break;
                        }

                        // Decrement places_restantes
                        $new_places = $places_restantes - $quantite;
                        $uq = "UPDATE evenements SET places_restantes = :new_places WHERE id_event = :id_event";
                        $su = $db->prepare($uq);
                        $su->bindParam(':new_places', $new_places);
                        $su->bindParam(':id_event', $event['id_event']);
                        $su->execute();
                    }

                    // Update reservation status
                    $uq2 = "UPDATE reservations SET statut = :statut WHERE id_reserv = :id_reserv";
                    $su2 = $db->prepare($uq2);
                    $su2->bindParam(':statut', $new_status);
                    $su2->bindParam(':id_reserv', $id_reserv);
                    $su2->execute();

                    $db->commit();
                    
                    // Calculate and return updated profit for the event
                    $profit = calculateEventProfit($db, $reserv['id_event']);
                    
                    $response['success'] = true;
                    $response['message'] = 'Statut mis à jour';
                    $response['event_profit'] = $profit;
                } catch (PDOException $e) {
                    $db->rollBack();
                    $response['message'] = 'Erreur mise à jour: ' . $e->getMessage();
                }
            }
            break;

        case 'get_event_profit':
            // Calculer le profit d'un ou tous les événements
            $id_event = $_GET['id_event'] ?? null;
            
            if ($id_event) {
                // Profit pour un événement spécifique
                $profit = calculateEventProfit($db, $id_event);
                $response['success'] = true;
                $response['event_id'] = $id_event;
                $response['profit'] = $profit;
            } else {
                // Profit pour tous les événements
                $query = "SELECT id_event, titre FROM evenements ORDER BY date_event ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $profits = [];
                foreach ($events as $event) {
                    $profit = calculateEventProfit($db, $event['id_event']);
                    $profits[] = [
                        'event_id' => $event['id_event'],
                        'event_title' => $event['titre'],
                        'profit' => $profit
                    ];
                }
                
                $response['success'] = true;
                $response['profits'] = $profits;
            }
            break;

        default:
            $response['message'] = 'Action non reconnue: ' . $action;
            break;
    }
} catch(PDOException $e) {
    $response['message'] = 'Erreur PDO: ' . $e->getMessage();
}

echo json_encode($response);
?>