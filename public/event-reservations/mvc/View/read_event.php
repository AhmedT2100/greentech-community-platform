<?php
// List view that uses the Event model directly to fetch data (server-side)
include_once __DIR__ . '/../Config/config.php';
include_once __DIR__ . '/../Model/Event.php';

$database = new Database();
$db = $database->getConnection();
$events = [];
if ($db) {
  $eventModel = new Event($db);
  $events = $eventModel->getAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Liste des événements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 </head>
<body class="p-4">
  <div class="container">
    <h1>Événements</h1>
    <p><a class="btn btn-success" href="create_event.php">Créer un événement</a></p>
    <?php if (empty($events)): ?>
      <div class="alert alert-info">Aucun événement trouvé.</div>
    <?php else: ?>
      <table class="table table-striped">
        <thead>
          <tr><th>ID</th><th>Titre</th><th>Date</th><th>Lieu</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach($events as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['id_event']) ?></td>
            <td><?= htmlspecialchars($e['titre']) ?></td>
            <td><?= htmlspecialchars($e['date_event']) ?></td>
            <td><?= htmlspecialchars($e['lieu']) ?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="update_event.php?id=<?= urlencode($e['id_event']) ?>">Modifier</a>
              <a class="btn btn-sm btn-danger" href="delete_event.php?id=<?= urlencode($e['id_event']) ?>">Supprimer</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
