<?php
include_once __DIR__ . '/../Config/config.php';
include_once __DIR__ . '/../Model/Event.php';

$database = new Database();
$db = $database->getConnection();
$event = null;
if ($db) {
    $eventModel = new Event($db);
    $id = $_GET['id'] ?? null;
    if ($id) $event = $eventModel->getById($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && isset($_POST['id'])) {
    $eventModel->delete($_POST['id']);
    header('Location: read_event.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Supprimer Événement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 </head>
<body class="p-4">
  <div class="container">
    <h1>Supprimer un événement</h1>
    <?php if (!$event): ?>
      <div class="alert alert-warning">Événement introuvable</div>
      <a class="btn btn-secondary" href="read_event.php">Retour</a>
    <?php else: ?>
      <div class="alert alert-danger">
        Voulez-vous vraiment supprimer l'événement "<?= htmlspecialchars($event['titre']) ?>" ?
      </div>
      <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($event['id_event']) ?>">
        <button type="submit" name="confirm" value="1" class="btn btn-danger">Supprimer</button>
        <a class="btn btn-secondary" href="read_event.php">Annuler</a>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
