<?php

require_once '../../../config.php';
require_once '../../Model/User.php';
require_once '../../Controller/UserController.php';

$userController = new \Controller\UserController();

$users = $userController->readUsers();

if ($users) {
    echo json_encode(['success' => true, 'users' => $users]);
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur trouvé.']);
}

?>