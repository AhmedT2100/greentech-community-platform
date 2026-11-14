<?php
session_start();
// require_once '../../app/utils/Language.php';
require_once '../config.php';
require_once 'app/controllers/ArticleController.php';

// ✅ CHECK IF USER IS LOGGED IN - STRICT CHECK
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redirect to index.php which will display login modal
    header('Location: index.php?login=required');
    exit;
}

$db = new Database();
$pdo = $db->getConnection();
$ctrl = new ArticleController($pdo);

// Get logged-in user info
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Anonymous';

// Debug: Log the session info
error_log("DEBUG create.php - userId: $userId, userName: $userName");
error_log("DEBUG create.php - Full SESSION: " . json_encode($_SESSION));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Make sure an image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = time() . '_' . $_FILES['image']['name'];
        $imageTmp = $_FILES['image']['tmp_name'];
        $uploadDir = __DIR__ . '/../uploads/';

        if (move_uploaded_file($imageTmp, $uploadDir . $imageName)) {
            $data = [
                'title' => $_POST['title'],
                'content' => $_POST['content'],
                'image' => $imageName,
                'user_id' => $userId,
                'user_name' => $userName
            ];

            // Create the article
            $ctrl->create($data);

            // Redirect to index.php after success
            header('Location: index.php');
            exit();
        } else {
            $error = "Failed to upload image.";
        }
    } else {
        $error = "Please select an image to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un article - GreenTech</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS File -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="../vendor/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../vendor/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../vendor/lightbox/css/lightbox.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">
    
    <style>
        /* Extend banner to cover navigation */
        .banner {
            margin-top: -92px;
            padding-top: 200px !important;
        }
        /* Create Article Page Styles */
        body {
            padding-top: 92px;
        }

        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            margin-top: 60px;
        }

        .create-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 20px;
        }

        .create-header h1 {
            font-size: 32px;
            color: #353535;
            margin: 0;
        }

        .create-header a {
            background: #1dbf73;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            white-space: nowrap;
        }

        .create-header a:hover {
            background: #15a051;
            color: white;
        }

        .create-page {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .create-page form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .create-page > p {
            color: #ef4444;
            margin-bottom: 20px;
        }

        .create-page div {
            display: flex;
            flex-direction: column;
        }

        .create-page label {
            font-weight: 600;
            color: #353535;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .create-page input[type="text"],
        .create-page textarea,
        .create-page input[type="file"] {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 14px;
            transition: 0.3s;
        }

        .create-page input[type="text"]:focus,
        .create-page textarea:focus,
        .create-page input[type="file"]:focus {
            outline: none;
            border-color: #1dbf73;
            box-shadow: 0 0 0 3px rgba(29, 191, 115, 0.1);
        }

        .create-page textarea {
            resize: vertical;
            min-height: 200px;
        }

        .create-page button {
            background: #1dbf73;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            align-self: flex-start;
        }

        .create-page button:hover {
            background: #15a051;
        }

        @media (max-width: 768px) {
            .create-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .create-header a {
                width: 100%;
                text-align: center;
            }
        }
    </style>

</head>
<body>

<!-- Header Section Start -->
<header class="header">
    <div class="container-fluid">
        <div class="logo pull-left">
            <h1><a href="../index.html">GreenTechHub</a></h1>
        </div>

        <nav id="nav-menu-container">
            <ul class="nav-menu">
                <li><a href="../index.html">Home</a></li>
                <li><a href="../about.html">About</a></li>
                <li><a href="../service.html">Services</a></li>
                <li><a href="../portfolio.html">Portfolio</a></li>
                <li><a href="../events.html">Events</a></li>
                <li><a href="../contact.html">Contact</a></li>
                <li id="loginLink"><a href="#" onclick="openLoginModal(); return false;">Connexion</a></li>
                <li id="signupLink"><a href="#" onclick="openSignupModal(); return false;">Inscription</a></li>
                <li id="adminLink" style="display:none;"><a href="../admin.html">Admin</a></li>
            </ul>
        </nav>
    </div>
</header>
<!-- Header Section End -->

<!-- Banner Section Start-->
<section class="banner">
    <div class="container">
        <div class="col-lg-12">
            <div class="banner-nav">
                <div class="banner-box">
                    <a href="../index.html">Home </a>
                    <a href="index.php">Articles</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End-->

<!-- Hero/Breadcrumb Section -->
<section class="hero-section">
    <div class="hero-content">
        <nav class="breadcrumb-nav">
            <a href="../index.html">Home</a>
            <span></span>
            <a href="index.php">Articles</a>
            <span></span>
            <span>Créer</span>
        </nav>
    </div>
</section>

<div class="main-content">
    <div class="create-header">
        <h1>➕ Créer un nouvel article</h1>
        <a href="index.php" class="btn-primary">← Retour aux articles</a>
    </div>

    <div class="create-page">

    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="create.php" method="POST" enctype="multipart/form-data">
        <div>
            <label for="title">Titre :</label>
            <input type="text" id="title" name="title" required placeholder="Entrez le titre de l'article">
        </div>

        <div>
            <label for="content">Contenu :</label>
            <textarea id="content" name="content" rows="8" cols="50" required placeholder="Écrivez le contenu de votre article..."></textarea>
        </div>

        <div>
            <label for="image">Image :</label>
            <input type="file" id="image" name="image" accept="image/*" required>
        </div>

        <button type="submit" class="btn-primary">Créer l'article</button>
    </form>
    </div>
</div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Connexion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Accédez à votre tableau de bord GreenTechHub</p>
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="loginEmail" placeholder="votre@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="loginPassword" placeholder="Votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-log-in"></i> Se connecter
                    </button>
                </form>
                <p class="text-center mb-0">Pas encore de compte ? <a href="#" onclick="switchToSignup()">Créer un compte</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Signup Modal -->
<div class="modal fade" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="ion-md-leaf"></i> Créer un compte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">Rejoignez la communauté GreenTechHub</p>
                <form id="signupForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="firstName" placeholder="Votre prénom" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ion-md-person"></i></span>
                                <input type="text" class="form-control" id="lastName" placeholder="Votre nom" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adresse Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-mail"></i></span>
                            <input type="email" class="form-control" id="signupEmail" placeholder="votre@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="signupPassword" placeholder="Au moins 8 caractères" required>
                        </div>
                        <div style="margin-top: 8px; height: 5px; background: #e0e0e0; border-radius: 3px;">
                            <div id="passwordStrength" style="height: 100%; width: 0%; border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmer mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ion-md-lock"></i></span>
                            <input type="password" class="form-control" id="confirmPassword" placeholder="Confirmez votre mot de passe" required>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="acceptTerms" required>
                        <label class="form-check-label" for="acceptTerms">J'accepte les conditions d'utilisation</label>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="ion-md-person-add"></i> Créer mon compte
                    </button>
                </form>
                <p class="text-center mb-0">Déjà inscrit ? <a href="#" onclick="switchToLogin()">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../auth-ui.js"></script>
<script src="../js/main.js"></script>
<script src="js/controls.js"></script>
</body>
</html>
