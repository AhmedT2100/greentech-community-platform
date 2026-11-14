<?php
require_once __DIR__ . '/../models/categorie.php';
require_once __DIR__ . '/../controllers/categorie_controller.php';

$error = '';
$categorieC = new CategorieController();

if (isset($_POST['nom'], $_POST['description'])) {

    if (!empty($_POST['nom'])) {

        $categorie = new Categorie(
            $_POST['id'],
            $_POST['nom'],
            $_POST['description']
        );

        $categorieC->addCategorie($categorie);
        header('Location: read_categorie.php');
        exit();
    }
    else $error = "Veuillez remplir tous les champs !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter une Catégorie</title>

<style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; }
    .card {
        width: 450px; margin: 40px auto; background: white;
        padding: 25px; border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
    .form-group label { font-weight: bold; margin-bottom: 6px; }
    .form-group input, .form-group textarea {
        padding: 10px; border-radius: 6px; border: 1px solid #aaa;
    }
    button {
        width: 100%; padding: 12px; background: #28a745; color: white;
        border: none; border-radius: 8px; cursor: pointer; font-size: 16px;
        margin-top: 15px;
    }
    button:hover { background: #218838; }
</style>
</head>

<body>

<div class="card">
    <h2>Ajouter une catégorie</h2>
    <div id="formContainer"></div>
</div>

<script>
// Formulaire généré en JS (même style)
document.getElementById("formContainer").innerHTML = `
<form method="POST">

    <div class="form-group">
        <label>ID Catégorie (optionnel, laisser vide pour auto-génération)</label>
        <input type="number" name="id" min="1" placeholder="ID de la catégorie">
    </div>

    <div class="form-group">
        <label>Nom de la catégorie *</label>
        <input type="text" name="nom" required>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4"></textarea>
    </div>

    <button type="submit">Ajouter</button>
</form>
`;
</script>

</body>
</html>
