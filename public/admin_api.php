<?php

// admin_api.php - API pour l'administration



include 'config.php';



// Vérifier l'authentification

session_start();

if (!isset($_SESSION['user_id'])) {

    echo json_encode(array('success' => false, 'message' => 'Non authentifié'));

    exit;

}



$database = new Database();

$db = $database->getConnection();



// Vérifier si l'utilisateur est admin

try {

    $checkRoleQuery = "SELECT role FROM utilisateur WHERE id_user = :user_id";

    $checkRoleStmt = $db->prepare($checkRoleQuery);

    $checkRoleStmt->bindParam(':user_id', $_SESSION['user_id']);

    $checkRoleStmt->execute();

    $userRole = $checkRoleStmt->fetch(PDO::FETCH_ASSOC);



    if (!$userRole || $userRole['role'] !== 'admin') {

        echo json_encode(array('success' => false, 'message' => 'Accès non autorisé - Admin requis'));

        exit;

    }

} catch (PDOException $e) {

    echo json_encode(array('success' => false, 'message' => 'Erreur de vérification des droits'));

    exit;

}



$response = array('success' => false, 'message' => '');



// Déterminer l'action

$action = $_GET['action'] ?? ($_POST['action'] ?? '');



try {

    switch ($action) {

        case 'stats':

            // Récupérer les statistiques

            $stats = array();



            // Total utilisateurs

            $query = "SELECT COUNT(*) as total FROM utilisateur";

            $stmt = $db->prepare($query);

            $stmt->execute();

            $stats['totalUsers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



            // Total innovations (depuis l'historique)

            $query = "SELECT COUNT(DISTINCT id_innovation) as total FROM historique_innovation";

            $stmt = $db->prepare($query);

            $stmt->execute();

            $stats['totalInnovations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



            // Total administrateurs

            $query = "SELECT COUNT(*) as total FROM utilisateur WHERE role = 'admin'";

            $stmt = $db->prepare($query);

            $stmt->execute();

            $stats['totalAdmins'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $response['success'] = true;
            $response['stats'] = $stats;
            break;

        case 'users':
            // Récupérer tous les utilisateurs
            $query = "SELECT id_user, nom, email, role, is_banned FROM utilisateur ORDER BY id_user DESC";
            $stmt = $db->prepare($query);
            try {
                $stmt->execute();
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1054) {
                    $db->exec("ALTER TABLE utilisateur ADD COLUMN is_banned TINYINT(1) DEFAULT 0");
                    $stmt->execute();
                } else {
                    throw $e;
                }
            }
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['users'] = $users;
            break;

        case 'history':
            // Récupérer l'historique des innovations avec les noms d'utilisateurs

            $query = "SELECT h.id_historique, h.id_user, u.nom as nom_utilisateur, h.id_innovation, h.date_post 

                     FROM historique_innovation h 

                     JOIN utilisateur u ON h.id_user = u.id_user 

                     ORDER BY h.date_post DESC";

            $stmt = $db->prepare($query);

            $stmt->execute();



            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;

            $response['history'] = $history;

            break;



        case 'update_user':

            // Mettre à jour un utilisateur

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $id = $_POST['id'] ?? '';

                $nom = $_POST['nom'] ?? '';

                $email = $_POST['email'] ?? '';

                $role = $_POST['role'] ?? '';



                if (!empty($id) && !empty($nom) && !empty($email) && !empty($role)) {

                    // Vérifier si l'email existe déjà pour un autre utilisateur

                    $checkQuery = "SELECT id_user FROM utilisateur WHERE email = :email AND id_user != :id";

                    $checkStmt = $db->prepare($checkQuery);

                    $checkStmt->bindParam(':email', $email);

                    $checkStmt->bindParam(':id', $id);

                    $checkStmt->execute();



                    if ($checkStmt->rowCount() > 0) {

                        $response['message'] = 'Cet email est déjà utilisé par un autre utilisateur';

                    } else {

                        $updateQuery = "UPDATE utilisateur SET nom = :nom, email = :email, role = :role 

                                       WHERE id_user = :id";

                        $updateStmt = $db->prepare($updateQuery);

                        $updateStmt->bindParam(':nom', $nom);

                        $updateStmt->bindParam(':email', $email);

                        $updateStmt->bindParam(':role', $role);

                        $updateStmt->bindParam(':id', $id);



                        if ($updateStmt->execute()) {

                            $response['success'] = true;

                            $response['message'] = 'Utilisateur mis à jour avec succès';

                        } else {

                            $response['message'] = 'Erreur lors de la mise à jour';

                        }

                    }

                } else {

                    $response['message'] = 'Tous les champs sont requis';

                }

            }

            break;



        case 'delete_user':

            // Supprimer un utilisateur

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $id = $_POST['id'] ?? '';



                if (!empty($id)) {

                    // Empêcher la suppression de son propre compte

                    if ($id == $_SESSION['user_id']) {

                        $response['message'] = 'Vous ne pouvez pas supprimer votre propre compte';

                    } else {

                        $deleteQuery = "DELETE FROM utilisateur WHERE id_user = :id";

                        $deleteStmt = $db->prepare($deleteQuery);

                        $deleteStmt->bindParam(':id', $id);



                        if ($deleteStmt->execute()) {

                            $response['success'] = true;

                            $response['message'] = 'Utilisateur supprimé avec succès';

                        } else {

                            $response['message'] = 'Erreur lors de la suppression';

                        }

                    }

                } else {

                    $response['message'] = 'ID utilisateur requis';

                }

            }

            break;

        case 'ban_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    if ($id == $_SESSION['user_id']) {
                        $response['message'] = 'Vous ne pouvez pas vous bannir vous-même';
                    } else {
                        $stmt = $db->prepare("UPDATE utilisateur SET is_banned = 1 WHERE id_user = :id");
                        if ($stmt->execute([':id' => $id])) {
                            $response['success'] = true;
                            $response['message'] = 'Utilisateur banni avec succès';
                        } else {
                            $response['message'] = 'Erreur lors du bannissement';
                        }
                    }
                } else {
                    $response['message'] = 'ID utilisateur requis';
                }
            }
            break;

        case 'unban_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    $stmt = $db->prepare("UPDATE utilisateur SET is_banned = 0 WHERE id_user = :id");
                    if ($stmt->execute([':id' => $id])) {
                        $response['success'] = true;
                        $response['message'] = 'Utilisateur débanni avec succès';
                    } else {
                        $response['message'] = 'Erreur lors du débannissement';
                    }
                } else {
                    $response['message'] = 'ID utilisateur requis';
                }
            }
            break;



        default:

            $response['message'] = 'Action non reconnue';

            break;

    }

} catch (PDOException $e) {

    $response['message'] = 'Erreur de base de données: ' . $e->getMessage();

}



echo json_encode($response);

?>