<?php
require_once __DIR__ . '/../controllers/categorie_controller.php';
require_once __DIR__ . '/../models/categorie.php';

$categorieC = new CategorieController();
$error = '';
$categorie = null;

// Vérifier si l'ID est présent
if (isset($_GET['id'])) {
    $categorie = $categorieC->getCategorieById($_GET['id']);
}

if (!$categorie) {
    die("Catégorie introuvable !");
}

// Quand on soumet le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_POST['nom'])) {

        // Création d'une entité catégorie avec les données modifiées
        $categorieUpdated = new Categorie(
            $_POST['id'],           // id
            $_POST['nom'],          // nom
            $_POST['description']   // description (peut être vide)
        );

        // Mise à jour
        $categorieC->updateCategorie($categorieUpdated);

        header('Location: read_categorie.php');
        exit();

    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier une catégorie</title>

<style>
    body { font-family: Arial; background:#f4f4f4; }
    h2 { text-align:center; margin-top:25px; }
    .form-container {
        width:60%; background:white; padding:25px; border-radius:10px;
        margin:auto; margin-top:20px;
        box-shadow:0 0 15px rgba(0,0,0,0.1);
    }
    input, textarea {
        width:100%; padding:10px; margin-top:8px; margin-bottom:15px;
        border-radius:6px; border:1px solid #bbb; font-size:15px;
    }
    button {
        background:#007bff; padding:12px 18px; border:none;
        color:white; border-radius:6px; font-size:16px; cursor:pointer;
    }
    button:hover { background:#0056b3; }
    .error { text-align:center; color:red; font-weight:bold; }
</style>

</head>
<body>

<h2>Modifier une catégorie</h2>

<?php if ($error): ?>
    <p class="error"><?= $error ?></p>
<?php endif; ?>

<div id="formContainer" class="form-container"></div>

<script>
// On envoie la catégorie au JS
const categorie = <?= json_encode($categorie) ?>;

document.getElementById("formContainer").innerHTML = `
<form method="POST">

    <input type="hidden" name="id" value="${categorie.id_categorie}">

    <label>Nom :</label>
    <input type="text" name="nom" value="${categorie.nom}">

    <label>Description :</label>
    <textarea name="description">${categorie.description}</textarea>

    <button type="submit">Mettre à jour</button>
</form>
`;
</script>

</body>
</html>
