<?php

require_once '../../../config.php';
require_once '../../Model/User.php';
require_once '../../Controller/UserController.php';

use Controller\UserController;

$userController = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'role' => $_POST['role'] ?? 'user'
    ];

    if ($id) {
        $result = $userController->updateUser($id, $data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur lors de la mise à jour de l'utilisateur."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant.']);
    }
    exit;
}

?>
// Formulaire de modification d'utilisateur