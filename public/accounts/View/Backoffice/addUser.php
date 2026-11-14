<?php

require_once '../../../config.php';
require_once '../../Model/User.php';
require_once '../../Controller/UserController.php';

use Controller\UserController;

$userController = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom'] ?? '',
        'email' => $_POST['email'] ?? '',
        'mot_de_passe' => password_hash($_POST['mot_de_passe'] ?? '', PASSWORD_DEFAULT),
        'role' => $_POST['role'] ?? 'user'
    ];

    $result = $userController->createUser($data);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur ajouté avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de l'ajout de l'utilisateur."]);
    }
    exit;
} else {
    echo "<form method='POST'>
            <label>Nom: <input type='text' name='nom' required></label><br>
            <label>Email: <input type='email' name='email' required></label><br>
            <label>Mot de passe: <input type='password' name='mot_de_passe' required></label><br>
            <label>Rôle: <select name='role'>
                <option value='user'>Utilisateur</option>
                <option value='admin'>Administrateur</option>
            </select></label><br>
            <button type='submit'>Ajouter</button>
          </form>";
}

?>