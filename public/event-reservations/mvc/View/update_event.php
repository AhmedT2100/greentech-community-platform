<?php
include_once __DIR__ . '/../Config/config.php';
include_once __DIR__ . '/../Model/Event.php';

$database = new Database();
$db = $database->getConnection();
$event = null;
if ($db) {
		$eventModel = new Event($db);
		$id = $_GET['id'] ?? null;
		if ($id) {
				// getById() initializes the Event model but does not return the row,
				// so call it and then map the model properties to an array for the view.
				$eventModel->getById($id);
				$event = [
					'id_event' => $eventModel->id_event ?? null,
					'titre' => $eventModel->titre ?? '',
					'description' => $eventModel->description ?? '',
					'categorie' => $eventModel->categorie ?? '',
					'date_event' => $eventModel->date_event ?? '',
					'heure_debut' => $eventModel->heure_debut ?? '',
					'heure_fin' => $eventModel->heure_fin ?? '',
					'lieu' => $eventModel->lieu ?? '',
					'prix' => $eventModel->prix ?? null,
					'places_total' => $eventModel->places_total ?? null,
					'places_restantes' => $eventModel->places_restantes ?? null,
					'statut' => $eventModel->statut ?? 'inactif'
				];
		}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Modifier Événement</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 </head>
<body class="p-4">
	<div class="container">
		<h1>Modifier un événement</h1>
		<?php if (!$event): ?>
			<div class="alert alert-warning">Événement introuvable</div>
			<a class="btn btn-secondary" href="read_event.php">Retour à la liste</a>
		<?php else: ?>
		<form method="post" action="../Controller/event_controller.php?action=api_update">
			<input type="hidden" name="id" value="<?= htmlspecialchars($event['id_event']) ?>">
			<div class="mb-3">
				<label class="form-label">Titre</label>
				<input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($event['titre']) ?>" required>
			</div>
			<div class="mb-3">
				<label class="form-label">Description</label>
				<textarea name="description" class="form-control"><?= htmlspecialchars($event['description']) ?></textarea>
			</div>
			<div class="mb-3">
				<label class="form-label">Catégorie</label>
				<input type="text" name="categorie" class="form-control" value="<?= htmlspecialchars($event['categorie']) ?>">
			</div>
			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label">Date</label>
					<input type="date" name="date_event" class="form-control" value="<?= htmlspecialchars($event['date_event']) ?>" required>
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Heure début</label>
					<input type="time" name="heure_debut" class="form-control" value="<?= htmlspecialchars($event['heure_debut']) ?>">
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Heure fin</label>
					<input type="time" name="heure_fin" class="form-control" value="<?= htmlspecialchars($event['heure_fin']) ?>">
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label">Lieu</label>
				<input type="text" name="lieu" class="form-control" value="<?= htmlspecialchars($event['lieu']) ?>">
			</div>
			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label">Prix</label>
					<input type="number" step="0.01" name="prix" class="form-control" value="<?= htmlspecialchars($event['prix']) ?>">
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Places total</label>
					<input type="number" name="places_total" class="form-control" value="<?= htmlspecialchars($event['places_total']) ?>">
				</div>
				<div class="col-md-4 mb-3">
					<label class="form-label">Places restantes</label>
					<input type="number" name="places_restantes" class="form-control" value="<?= htmlspecialchars($event['places_restantes']) ?>">
				</div>
			</div>
			<div class="mb-3">
				<label class="form-label">Statut</label>
				<select name="statut" class="form-control">
					<option value="actif" <?= ($event['statut'] === 'actif') ? 'selected' : '' ?>>Actif</option>
					<option value="inactif" <?= ($event['statut'] === 'inactif') ? 'selected' : '' ?>>Inactif</option>
				</select>
			</div>
			<button class="btn btn-primary" type="submit">Enregistrer</button>
			<a class="btn btn-secondary" href="read_event.php">Annuler</a>
		</form>
		<?php endif; ?>
	</div>
</body>
</html>

