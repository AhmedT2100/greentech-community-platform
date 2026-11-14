<?php
require_once __DIR__ . '/../controllers/categorie_controller.php';

$categorieC = new CategorieController();
$liste = $categorieC->listCategories();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des catégories</title>

<style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; }
    h2 { text-align: center; margin-top: 30px; font-size: 28px; }
    .top-btn { width: 90%; margin: 20px auto; text-align: right; }
    .btn {
        padding: 8px 15px; color: white; text-decoration: none;
        border-radius: 6px; margin-left: 4px; font-size: 14px;
    }
    .add-btn { background: #28a745; }
    .edit-btn { background: #007bff; }
    .delete-btn { background: #dc3545; }

    table {
        width: 90%; margin: 20px auto; border-collapse: collapse;
        background: white; box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px; border: 1px solid #ccc; text-align: center;
    }
    th { background: #f9f9f9; }
    tr:hover { background: #fafafa; }
    .actions { display: flex; justify-content: center; gap: 8px; }
</style>

</head>
<body>

<h2>Liste des catégories</h2>

<div class="top-btn">
    <a href="create_categorie.php" class="btn add-btn">+ Ajouter une catégorie</a>
</div>

<table>
<tr>
    <th>ID</th>
    <th>Nom</th>
    <th>Description</th>
    <th>Actions</th>
</tr>

<?php foreach ($liste as $c): ?>
<tr>
    <td><?= $c['id_categorie'] ?></td>
    <td><?= htmlspecialchars($c['nom']) ?></td>
    <td><?= htmlspecialchars($c['description']) ?></td>

    <td class="actions">
        <a class="btn edit-btn" href="update_categorie.php?id=<?= $c['id_categorie'] ?>">Modifier</a>
        <a class="btn delete-btn" href="delete_categorie.php?id=<?= $c['id_categorie'] ?>"
           onclick="return confirm('Supprimer cette catégorie ?');">
           Supprimer
        </a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
