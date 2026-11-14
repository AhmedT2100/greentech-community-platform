<?php
require_once __DIR__ . '/controllers/innovation_controller.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stars = isset($_GET['stars']) ? (int)$_GET['stars'] : 0;

$controller = new InnovationController();

if ($id > 0 && $stars >= 1 && $stars <= 5) {
    $controller->voteInnovation($id, $stars);
}

// Préserver les paramètres de filtrage/tri dans la redirection
$params = [];
if (isset($_GET['tab'])) $params['tab'] = $_GET['tab'];
if (isset($_GET['search'])) $params['search'] = $_GET['search'];
if (isset($_GET['statut'])) $params['statut'] = $_GET['statut'];
if (isset($_GET['sort'])) $params['sort'] = $_GET['sort'];
if (isset($_GET['dir'])) $params['dir'] = $_GET['dir'];
if (isset($_GET['page'])) $params['page'] = $_GET['page'];

$redirect = 'index.php' . (!empty($params) ? '?' . http_build_query($params) : '');
header("Location: $redirect");
exit;

