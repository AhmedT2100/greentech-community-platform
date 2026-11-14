<?php
session_start();
require_once __DIR__ . '/app/utils/Language.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function for relative dates
function getRelativeDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $interval = $now->diff($date);
    
    if ($interval->days == 0) {
        if ($interval->h == 0) {
            return 'maintenant';
        }
        return 'il y a ' . $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
    } elseif ($interval->days < 7) {
        return 'il y a ' . $interval->days . ' jour' . ($interval->days > 1 ? 's' : '');
    } else {
        return date('d/m/Y', strtotime($dateString));
    }
}

// Include database connection and controller
require_once __DIR__ . '/../config.php';
// require_once __DIR__ . '/../../app/controllers/ArticleController.php';

// Create DB connection
$db = new Database();
$pdo = $db->getConnection();

// Create controller instance
// $ctrl = new ArticleController($pdo);

// Get user ID (use IP if not logged in)
$userId = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];

// Check if viewing saved articles
$showSaved = isset($_GET['saved']) && $_GET['saved'] === '1';

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'latest'; // latest, trending, oldest
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Helper function to calculate reading time (words / 200 = minutes)
function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $readingTime = ceil($wordCount / 200);
    return $readingTime;
}

// Fetch articles with search and filters
if ($showSaved) {
    // Get saved articles (with error handling)
    try {
        $query = "SELECT a.* FROM articles a
                  INNER JOIN saved_articles s ON a.id = s.article_id
                  WHERE s.user_id = ?";
        $params = [$userId];
        
        if ($search) {
            $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY s.saved_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $articles = [];
    }
} else {
    // Get all articles with filters
    $query = "SELECT a.* FROM articles a WHERE 1=1";
    $params = [];
    
    if ($search) {
        $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($dateFrom) {
        $query .= " AND DATE(a.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND DATE(a.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    // Add sorting
    switch ($sortBy) {
        case 'trending':
            $query .= " ORDER BY a.likes DESC, a.created_at DESC";
            break;
        case 'oldest':
            $query .= " ORDER BY a.created_at ASC";
            break;
        default: // latest
            $query .= " ORDER BY a.created_at DESC";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fallback: if no articles, show demo ones (only for all articles view)
if (empty($articles) && !$showSaved) {
    $articles = [
        [
            'id' => 0,
            'title' => 'Welcome to GreenTech 🌱',
            'content' => 'This is a demo article showing how your website will look.',
            'image' => 'demo1.jpg',  // put a demo image in uploads/
            'created_at' => date('Y-m-d')
        ],
        [
            'id' => 0,
            'title' => 'Eco-Friendly Innovations',
            'content' => 'Discover the latest innovations in recycling, renewable energy, and sustainable living.',
            'image' => 'demo2.jpg',
            'created_at' => date('Y-m-d')
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTech Articles</title>

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
    
    <style>
        /* Article Page Styles */
        body {
            padding-top: 92px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            margin-top: 60px;
        }

        .index-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            gap: 20px;
        }

        .index-title-section h1 {
            font-size: 36px;
            color: #353535;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: #656565;
        }

        .btn-create-article {
            background: #1dbf73;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            white-space: nowrap;
        }

        .btn-create-article:hover {
            background: #15a051;
            color: white;
        }

        .search-container {
            margin-bottom: 30px;
            position: relative;
        }

        .search-bar {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: 0.3s;
        }

        .search-bar:focus {
            outline: none;
            border-color: #1dbf73;
            box-shadow: 0 0 0 3px rgba(29, 191, 115, 0.1);
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-top: 10px;
            z-index: 100;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-input,
        .filter-select {
            flex: 1;
            min-width: 150px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #1dbf73;
            box-shadow: 0 0 0 3px rgba(29, 191, 115, 0.1);
        }

        .btn-filter {
            padding: 10px 20px;
            background: #1dbf73;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filter:hover {
            background: #15a051;
            color: white;
        }

        .btn-reset {
            background: #656565;
        }

        .btn-reset:hover {
            background: #555;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #353535;
            border: 2px solid #ddd;
            border-radius: 25px;
            text-decoration: none;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 500;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #1dbf73;
            color: white;
            border-color: #1dbf73;
        }

        .articles-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .article-card {
            background: white;
            border-radius: 10px;
            overflow: visible;
            transition: 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .article-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }

        .article-card-image-wrapper {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .article-card-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.3s;
        }

        .article-card:hover .article-card-image-wrapper img {
            transform: scale(1.05);
        }

        .article-card-content {
            padding: 20px;
        }

        .article-card-link {
            text-decoration: none;
            color: inherit;
        }

        .article-card h2 {
            font-size: 18px;
            color: #353535;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .article-card p {
            font-size: 14px;
            color: #656565;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .article-stats {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #656565;
            border-top: 1px solid #eee;
            padding-top: 15px;
            flex-wrap: wrap;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .article-menu {
            position: relative;
            display: inline-block;
        }

        .article-menu-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 5px 10px;
            color: #656565;
            transition: all 0.3s ease;
            border-radius: 5px;
        }

        .article-menu-btn:hover {
            background: #f0f0f0;
            color: #1dbf73;
            transform: scale(1.1);
        }

        .article-menu-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            min-width: 180px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            margin-top: 8px;
        }

        .menu-item {
            display: block;
            width: 100%;
            padding: 12px 16px;
            text-decoration: none;
            color: #353535;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-align: left;
            font-family: inherit;
        }

        .menu-item:first-child {
            border-bottom: 1px solid #f0f0f0;
        }

        .menu-item:hover {
            background: #f8f9fa;
            padding-left: 20px;
        }

        .edit-item:hover {
            color: #1dbf73;
        }

        .delete-item:hover {
            color: #e74c3c;
            background: #fff5f5;
        }

        @media (max-width: 768px) {
            .index-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .articles-container {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-input,
            .filter-select {
                width: 100%;
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
                <li id="userInfo" style="display:none;"></li>
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

    </div>
</section>

<div class="main-content">
    <div class="index-header">
        <div class="index-title-section">
            <h1><?= $showSaved ? '📌 Mes Articles Sauvegardés' : '🌱 Articles GreenTech' ?></h1>
            <p class="subtitle"><?= $showSaved ? 'Vos articles préférés' : 'Découvrez nos derniers articles sur la durabilité' ?></p>
        </div>
        <!-- Create Article Button - Only for Logged-In Users -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="btn-primary btn-create-article">
                ➕ Créer un nouvel article
            </a>
        <?php else: ?>
            <button class="btn-primary btn-create-article" onclick="openLoginModal(); return false;" style="cursor: pointer;">
                🔒 Login pour créer
            </button>
        <?php endif; ?>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <input type="text" id="searchInput" class="search-bar" placeholder="🔍 Rechercher un article..." value="<?= htmlspecialchars($search) ?>" />
        <div id="searchResults" class="search-results" style="display: none;">
            <div class="search-results-list"></div>
        </div>
    </div>

    <!-- Advanced Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
                
                <select name="sort" class="filter-select">
                    <option value="latest" <?= $sortBy === 'latest' ? 'selected' : '' ?>>📅 Plus récent</option>
                    <option value="trending" <?= $sortBy === 'trending' ? 'selected' : '' ?>>🔥 Tendances (Plus likés)</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>⏰ Plus ancien</option>
                </select>
            </div>

            <div class="filter-row">
                <input type="date" name="date_from" placeholder="Du" value="<?= htmlspecialchars($dateFrom) ?>" class="filter-input">
                <input type="date" name="date_to" placeholder="Au" value="<?= htmlspecialchars($dateTo) ?>" class="filter-input">
                <button type="submit" class="btn-filter">🔎 Filtrer</button>
                <a href="index.php" class="btn-filter btn-reset">↺ Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Filter Buttons -->
    <div class="filter-buttons">
        <a href="index.php" class="filter-btn <?= !$showSaved ? 'active' : '' ?>">
            📰 Tous les articles
        </a>
        <a href="index.php?saved=1" class="filter-btn <?= $showSaved ? 'active' : '' ?>">
            🔖 Mes favoris
        </a>
    </div>

<div class="articles-container">
    <?php if (empty($articles)): ?>
        <div style="text-align: center; padding: 60px 20px; grid-column: 1 / -1;">
            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">
                <?= $showSaved ? '📌 Aucun article sauvegardé pour le moment' : '📰 Aucun article disponible' ?>
            </p>
            <?php if ($showSaved): ?>
                <a href="index.php" class="btn-primary" style="display: inline-block; padding: 10px 20px; text-decoration: none;">
                    ← Retour aux articles
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($articles as $article): 
            // Get article likes
            $likesStmt = $pdo->prepare("SELECT likes FROM articles WHERE id = ?");
            $likesStmt->execute([$article['id']]);
            $articleData = $likesStmt->fetch(PDO::FETCH_ASSOC);
            $likes = $articleData['likes'] ?? 0;
            
            // Get comment count
            $commentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE article_id = ?");
            $commentStmt->execute([$article['id']]);
            $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Check if current user is article owner
            $articleUserId = $article['user_id'] ?? null;
            $currentUserId = $_SESSION['user_id'] ?? null;
            $isArticleOwner = $articleUserId && $currentUserId && $articleUserId == $currentUserId;
        ?>
            <div class="article-card">
                <div class="article-card-image-wrapper">
                    <img src="../uploads/<?= htmlspecialchars($article['image']) ?>" alt="">
                </div>
                <div class="article-card-content">
                    <div class="article-card-header">
                        <a href="article.php?id=<?= $article['id'] ?>" class="article-card-link">
                            <h2><?= htmlspecialchars($article['title']) ?></h2>
                            <p><?= nl2br(htmlspecialchars(substr($article['content'], 0, 100))) ?>...</p>
                        </a>
                        <?php if ($isArticleOwner): ?>
                            <div class="article-menu">
                                <button class="article-menu-btn" data-article-id="<?= $article['id'] ?>" title="Menu d'article">⋮</button>
                                <div class="article-menu-dropdown" id="menu-<?= $article['id'] ?>" style="display: none;">
                                    <a href="edit_article.php?id=<?= $article['id'] ?>" class="menu-item edit-item">✏️ Modifier</a>
                                    <button type="button" class="menu-item delete-item" data-article-id="<?= $article['id'] ?>">🗑️ Supprimer</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="article-stats">
                        <span class="stat">👤 <?= htmlspecialchars($article['user_name'] ?? 'Anonyme') ?></span>
                        <span class="stat">❤️ <?= $likes ?></span>
                        <span class="stat">💬 <?= $commentCount ?></span>
                        <span class="stat">⏱️ <?= getReadingTime($article['content']) ?> min</span>
                        <span class="stat">📅 <?= getRelativeDate($article['created_at']) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
<script>
// Check if login is required and open modal
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('login') === 'required') {
        // Wait a moment for modals to be ready
        setTimeout(() => {
            openLoginModal();
        }, 500);
    }
});
</script>
</body>
</html>
