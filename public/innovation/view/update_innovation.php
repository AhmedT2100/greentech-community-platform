<?php
require_once __DIR__ . '/../controllers/innovation_controller.php';
require_once __DIR__ . '/../models/innovation.php';

$innovationC = new InnovationController();
$error = '';
$success = '';
$innovation = null;

/* ---- 1) Load innovation using GET id ---- */
if (isset($_GET['id'])) {
    $innovation = $innovationC->showInnovation($_GET['id']);
}

/* ---- 2) When form submitted ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['titre']) && !empty($_POST['description'])) {
        $innovationUpdated = new Innovation(
            $_POST['id'],
            $_POST['titre'],
            $_POST['description'],
            $innovation['id_categorie'] ?? null,
            $_POST['image'],
            $_POST['statut']
        );

        $innovationC->updateInnovation($innovationUpdated, $_POST['id']);
        $success = "Innovation mise à jour avec succès !";
        $innovation = $innovationC->showInnovation($_POST['id']);
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier une innovation - InnovDash</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* Variables CSS GreenTechHub */
        :root {
            --primary: #1FA67A;
            --primary-dark: #1b926c;
            --primary-light: #e8f7f0;
            --secondary: #2c3e50;
            --accent: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border: #dee2e6;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            --shadow-light: 0 5px 15px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.3s ease;
            --font-primary: 'Montserrat', sans-serif;
            --font-secondary: 'Nunito', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-secondary);
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-primary);
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 15px 0;
            transition: var(--transition);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 28px;
        }

        .logo h1 a i {
            margin-right: 10px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            margin: 0 5px;
        }

        .nav-menu a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 50px;
            transition: var(--transition);
            display: block;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary);
            background: var(--primary-light);
        }

        /* Hero Section simplifiée */
        .page-hero {
            background: linear-gradient(rgba(31, 166, 122, 0.9), rgba(31, 166, 122, 0.8)),
                url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
            color: white;
            text-align: center;
            padding: 150px 20px 80px;
            margin-top: 70px;
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
        }

        .page-hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .page-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.95;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px auto 20px;
            color: var(--gray);
            font-size: 0.95rem;
            max-width: 800px;
            flex-wrap: wrap;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .breadcrumb .separator {
            color: var(--border);
        }

        /* Innovation Info Banner */
        .innovation-info {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-light);
            padding: 25px;
            margin: 30px auto;
            max-width: 800px;
            border-left: 5px solid var(--primary);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .innovation-info-icon {
            background: var(--primary-light);
            color: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .innovation-info-content h3 {
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .innovation-info-content p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .innovation-id-badge {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-left: auto;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto 60px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .form-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 1rem;
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 166, 122, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231FA67A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 40px;
        }

        /* Image Preview */
        .image-preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-light);
            margin-top: 10px;
            display: none;
        }

        .no-image {
            background: var(--gray-light);
            padding: 40px;
            border-radius: var(--radius);
            text-align: center;
            color: var(--gray);
            font-style: italic;
        }

        /* Help text */
        .form-help {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Character counter */
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 6px;
        }

        .char-counter.warning {
            color: var(--warning);
        }

        .char-counter.danger {
            color: var(--danger);
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 6px 15px rgba(31, 166, 122, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(31, 166, 122, 0.4);
        }

        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-success:hover {
            background: #219653;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary-light);
        }

        .btn-gray {
            background: var(--gray-light);
            color: var(--gray);
            border: 1px solid var(--border);
        }

        .btn-gray:hover {
            background: var(--border);
            color: var(--dark);
        }

        /* Alerts */
        .alert {
            padding: 18px 24px;
            border-radius: var(--radius);
            margin: 25px auto;
            max-width: 800px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.4s ease;
            font-weight: 500;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: var(--transition);
        }

        .footer-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .footer-copyright {
            width: 100%;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .page-hero {
                padding: 130px 15px 60px;
                margin-top: 60px;
            }

            .page-hero h1 {
                font-size: 2.2rem;
            }

            .innovation-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .innovation-id-badge {
                margin-left: 0;
            }

            .form-container {
                padding: 25px;
                margin-bottom: 40px;
            }

            .form-header h2 {
                font-size: 1.8rem;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-menu li {
                margin: 5px 3px;
            }

            .nav-menu a {
                padding: 8px 15px;
                font-size: 0.95rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .page-hero h1 {
                font-size: 1.8rem;
            }

            .page-hero p {
                font-size: 1rem;
            }

            .breadcrumb {
                font-size: 0.85rem;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .innovation-info-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><a href="../index.php" class="navbar-brand"><i class="fas fa-lightbulb"></i> InnovDash</a></h1>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="create_innovation.php"><i class="fas fa-plus-circle"></i> Nouvelle Innovation</a>
                        </li>
                        <li><a href="read_innovation.php?id=<?= $_GET['id'] ?? '' ?>"><i class="fas fa-eye"></i> Voir
                                Détails</a></li>
                        <li><a href="../index.php?tab=innovations" class="active"><i class="fas fa-list"></i> Liste</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <h1><i class="fas fa-edit fa-beat"></i> Modifier l'Innovation</h1>
            <p>Mettez à jour les informations de l'innovation #<?= $innovation['id_innovation'] ?? '' ?></p>
        </div>
    </section>

    <main class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../index.php"><i class="fas fa-home"></i> Dashboard</a>
            <span class="separator">/</span>
            <a href="../index.php?tab=innovations">Innovations</a>
            <span class="separator">/</span>
            <?php if ($innovation): ?>
                <a
                    href="read_innovation.php?id=<?= $innovation['id_innovation'] ?>">#<?= $innovation['id_innovation'] ?></a>
                <span class="separator">/</span>
                <span>Modifier</span>
            <?php else: ?>
                <span>Innovation introuvable</span>
            <?php endif; ?>
        </div>

        <!-- Innovation Info Banner -->
        <?php if ($innovation): ?>
            <div class="innovation-info">
                <div class="innovation-info-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="innovation-info-content">
                    <h3><?= htmlspecialchars($innovation['titre']) ?></h3>
                    <p>Créée le <?= date('d/m/Y', strtotime($innovation['date_creation'])) ?></p>
                </div>
                <div class="innovation-id-badge">
                    #<?= $innovation['id_innovation'] ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="form-container">
            <?php if ($innovation): ?>
                <div class="form-header">
                    <h2><i class="fas fa-edit"></i> Formulaire de Modification</h2>
                    <p>Tous les champs marqués d'un <span style="color:var(--danger);">*</span> sont obligatoires</p>
                </div>

                <form method="POST" id="updateForm">
                    <input type="hidden" name="id" value="<?= $innovation['id_innovation'] ?>">

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-heading"></i>
                            Titre de l'innovation
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="titre" class="form-control"
                            value="<?= htmlspecialchars($innovation['titre']) ?>"
                            placeholder="Entrez le titre de l'innovation" required
                            oninput="updateCharCounter(this, 'titre-counter', 100)">
                        <div class="char-counter" id="titre-counter"><?= strlen($innovation['titre']) ?>/100 caractères
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Description
                            <span class="required">*</span>
                        </label>
                        <textarea name="description" class="form-control"
                            placeholder="Décrivez votre innovation en détail..." required rows="6"
                            oninput="updateCharCounter(this, 'desc-counter', 500)"><?= htmlspecialchars($innovation['description']) ?></textarea>
                        <div class="char-counter" id="desc-counter"><?= strlen($innovation['description']) ?>/500 caractères
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-image"></i>
                                URL de l'image
                            </label>
                            <input type="text" name="image" class="form-control"
                                value="<?= htmlspecialchars($innovation['image'] ?? '') ?>"
                                placeholder="ex: innovation-1.jpg" id="imageInput">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Nom du fichier dans le dossier uploads/
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-tag"></i>
                                Statut
                                <span class="required">*</span>
                            </label>
                            <select name="statut" class="form-control" required>
                                <option value="en attente" <?= $innovation['statut'] === "en attente" ? "selected" : "" ?>>
                                    En attente
                                </option>
                                <option value="validé" <?= $innovation['statut'] === "validé" ? "selected" : "" ?>>
                                    Validé
                                </option>
                            </select>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Détermine la visibilité de l'innovation
                            </div>
                        </div>
                    </div>

                    <!-- Image Preview -->
                    <div class="form-group" id="imagePreviewContainer">
                        <label class="form-label">
                            <i class="fas fa-image"></i>
                            Aperçu de l'image
                        </label>
                        <div class="image-preview-container">
                            <?php if (!empty($innovation['image'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($innovation['image']) ?>" alt="Aperçu"
                                    class="image-preview" id="imagePreview">
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    L'image sera mise à jour après sauvegarde
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-2x"></i>
                                    <p style="margin-top: 10px;">Aucune image associée</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <a href="read_innovation.php?id=<?= $innovation['id_innovation'] ?>" class="btn btn-outline">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <a href="../index.php?tab=innovations" class="btn btn-gray">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-exclamation-triangle fa-3x" style="color: var(--warning); margin-bottom: 20px;"></i>
                    <h2 style="color: var(--dark); margin-bottom: 15px;">Innovation introuvable</h2>
                    <p style="color: var(--gray); margin-bottom: 30px;">L'innovation que vous essayez de modifier n'existe
                        pas ou a été supprimée.</p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="../index.php?tab=innovations" class="btn btn-primary">
                            <i class="fas fa-list"></i> Voir toutes les innovations
                        </a>
                        <a href="create_innovation.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Créer une nouvelle innovation
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-lightbulb"></i> InnovDash
                </div>
                <div class="footer-links">
                    <a href="../index.php">Dashboard</a>
                    <a href="../index.php?tab=innovations">Innovations</a>
                    <a href="../index.php?tab=categories">Catégories</a>
                    <a href="create_innovation.php">Nouvelle Innovation</a>
                </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> InnovDash - Gestion des Innovations. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        // Fonction pour mettre à jour le compteur de caractères
        function updateCharCounter(input, counterId, maxLength) {
            const length = input.value.length;
            const counter = document.getElementById(counterId);
            if (counter) {
                counter.textContent = `${length}/${maxLength} caractères`;

                // Réinitialiser les classes
                counter.classList.remove('warning', 'danger');

                if (length > maxLength * 0.9 && length <= maxLength) {
                    counter.classList.add('warning');
                } else if (length > maxLength) {
                    counter.classList.add('danger');
                }
            }
        }

        // Gestion de l'aperçu d'image
        function setupImagePreview() {
            const imageInput = document.getElementById('imageInput');
            const imagePreview = document.getElementById('imagePreview');
            const previewContainer = document.getElementById('imagePreviewContainer');

            if (imageInput && imagePreview) {
                imageInput.addEventListener('input', function () {
                    if (this.value.trim() !== '') {
                        imagePreview.src = '../uploads/' + this.value;
                        imagePreview.style.display = 'block';
                    } else {
                        imagePreview.style.display = 'none';
                    }
                });
            }
        }

        // Initialiser les fonctionnalités
        document.addEventListener('DOMContentLoaded', function () {
            // Initialiser les compteurs pour les champs existants
            const titreInput = document.querySelector('input[name="titre"]');
            const descTextarea = document.querySelector('textarea[name="description"]');

            if (titreInput) updateCharCounter(titreInput, 'titre-counter', 100);
            if (descTextarea) updateCharCounter(descTextarea, 'desc-counter', 500);

            // Configuration de l'aperçu d'image
            setupImagePreview();

            // Effet de scroll sur la navigation
            window.addEventListener('scroll', function () {
                const header = document.querySelector('.header');
                if (window.scrollY > 50) {
                    header.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.1)';
                    header.style.padding = '10px 0';
                } else {
                    header.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
                    header.style.padding = '15px 0';
                }
            });

            // Validation du formulaire
            const form = document.getElementById('updateForm');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const titre = this.querySelector('input[name="titre"]').value.trim();
                    const description = this.querySelector('textarea[name="description"]').value.trim();

                    // Validation des champs obligatoires
                    if (!titre || !description) {
                        e.preventDefault();
                        alert('Veuillez remplir tous les champs obligatoires');
                        return false;
                    }

                    // Validation de la longueur du titre
                    if (titre.length > 100) {
                        e.preventDefault();
                        alert('Le titre ne peut pas dépasser 100 caractères');
                        return false;
                    }

                    // Validation de la longueur de la description
                    if (description.length > 500) {
                        e.preventDefault();
                        alert('La description ne peut pas dépasser 500 caractères');
                        return false;
                    }

                    // Confirmation avant soumission
                    if (!confirm('Confirmez-vous la mise à jour de cette innovation ?')) {
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });
            }

            // Focus sur le premier champ
            setTimeout(() => {
                const firstInput = document.querySelector('input[name="titre"]');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);

            // Animation des champs au focus
            const formControls = document.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.addEventListener('focus', function () {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });

                control.addEventListener('blur', function () {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>