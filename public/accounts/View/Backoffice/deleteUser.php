<?php

require_once '../../../config.php';
require_once '../../Model/User.php';
require_once '../../Controller/UserController.php';

use Controller\UserController;

$userController = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'] ?? null;

    if ($userId) {
        $result = $userController->deleteUser($userId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'utilisateur."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant.']);
    }
    exit;
}

?>