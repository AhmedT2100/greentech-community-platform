<?php
require_once __DIR__ . '/../controllers/categorie_controller.php';

$categorieC = new CategorieController();

$categorie = null;
if (isset($_GET['id'])) {
    $categorie = $categorieC->getCategorieById($_GET['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === "yes") {
        $categorieC->deleteCategorie($_POST['id']);
        header("Location: read_categorie.php?deleted=1");
        exit();
    } else {
        header("Location: read_categorie.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Supprimer une catégorie</title>
<meta charset="UTF-8">

<style>
    body { font-family: Arial; background: #f5f5f5; text-align:center; padding-top:60px; }
    .card {
        width:420px; margin:auto; background:white; padding:25px;
        border-radius:10px; box-shadow:0 0 12px rgba(0,0,0,0.2);
    }
    h2 { color:#c82333; }
    .btn {
        padding:10px 18px; border-radius:6px; text-decoration:none; color:white;
        margin:10px; display:inline-block;
    }
    .delete { background:#dc3545; }
    .cancel { background:#6c757d; }
</style>

</head>
<body>

<div class="card">

<?php if ($categorie): ?>
    <h2>Supprimer cette catégorie ?</h2>

    <p><strong>ID :</strong> <?= $categorie['id_categorie'] ?></p>
    <p><strong>Nom :</strong> <?= htmlspecialchars($categorie['nom']) ?></p>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $categorie['id_categorie'] ?>">

        <button class="btn delete" type="submit" name="confirm" value="yes">Oui, supprimer</button>
        <button class="btn cancel" type="submit" name="confirm" value="no">Annuler</button>
    </form>

<?php else: ?>
    <h2>Catégorie introuvable.</h2>
    <a href="read_categorie.php" class="btn cancel">Retour</a>
<?php endif; ?>

</div>

</body>
</html>
