<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Créer Événement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 </head>
<body class="p-4">
  <div class="container">
    <h1>Créer un événement</h1>
    <form method="post" action="../Controller/event_controller.php?action=api_create">
      <div class="mb-3">
        <label class="form-label">Titre</label>
        <input type="text" name="titre" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Catégorie</label>
        <input type="text" name="categorie" class="form-control">
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Date</label>
          <input type="date" name="date_event" class="form-control" required>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Heure début</label>
          <input type="time" name="heure_debut" class="form-control">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Heure fin</label>
          <input type="time" name="heure_fin" class="form-control">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Lieu</label>
        <input type="text" name="lieu" class="form-control">
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Prix</label>
          <input type="number" step="0.01" name="prix" class="form-control" value="0">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Places total</label>
          <input type="number" name="places_total" class="form-control" value="10">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control">
            <option value="actif">Actif</option>
            <option value="inactif">Inactif</option>
          </select>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Créer</button>
      <a class="btn btn-secondary" href="read_event.php">Annuler</a>
    </form>
  </div>
</body>
</html>
