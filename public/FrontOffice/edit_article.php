<?php
session_start();
require_once __DIR__ . '/../articles/app/config/Database.php';
require_once __DIR__ . '/../articles/app/controllers/ArticleController.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$db = new Database();
$pdo = $db->getConnection();
$articleCtrl = new ArticleController($pdo);

$articleId = intval($_GET['id'] ?? 0);
if (!$articleId) {
    die('Article non trouvé');
}

$article = $articleCtrl->get($articleId);
if (!$article) {
    die('Article non trouvé');
}

// Check if current user is the article owner
$currentUserId = $_SESSION['user_id'];
$articleUserId = $article['user_id'] ?? null;

if ($articleUserId !== $currentUserId) {
    die('Vous n\'avez pas la permission de modifier cet article');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image = $article['image']; // Keep existing image for now
    
    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . bin2hex(random_bytes(6)) . '_' . basename($_FILES['image']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            // Delete old image if it exists
            $oldImage = $uploadDir . $article['image'];
            if (file_exists($oldImage) && $article['image']) {
                unlink($oldImage);
            }
            $image = $fileName;
        }
    }
    
    // Validate inputs
    if (empty($title) || empty($content)) {
        $error = 'Le titre et le contenu sont requis';
    } else if (strlen($title) < 3) {
        $error = 'Le titre doit contenir au moins 3 caractères';
    } else if (strlen($content) < 10) {
        $error = 'Le contenu doit contenir au moins 10 caractères';
    } else {
        try {
            $articleCtrl->update($articleId, [
                'title' => $title,
                'content' => $content,
                'image' => $image
            ]);
            $success = 'Article modifié avec succès';
            // Refresh article data
            $article = $articleCtrl->get($articleId);
        } catch (Exception $e) {
            $error = 'Erreur lors de la modification: ' . $e->getMessage();
        }
    }
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article - GreenTechHub</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Main Template CSS -->
    <link href="../../public/css/style.css?v=<?= time(); ?>" rel="stylesheet">
    
    <style>
        .edit-article-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .edit-article-container h1 {
            color: #353535;
            margin-bottom: 30px;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #353535;
            font-family: 'Nunito', sans-serif;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1dbf73;
            box-shadow: 0 0 0 3px rgba(29, 191, 115, 0.1);
        }
        
        .form-group textarea {
            min-height: 300px;
            resize: vertical;
        }
        
        .current-image {
            margin-bottom: 20px;
        }
        
        .current-image img {
            max-width: 200px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-submit {
            background: #1dbf73;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Nunito', sans-serif;
        }
        
        .btn-submit:hover {
            background: #15a051;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 191, 115, 0.3);
        }
        
        .btn-cancel {
            background: #f0f2f5;
            color: #656565;
            padding: 12px 30px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Nunito', sans-serif;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
            border-color: #bbb;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-family: 'Nunito', sans-serif;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
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
                    <li><a href="../contact.html">Contact</a></li>
                    <li class="menu-active"><a href="index.php">Articles</a></li>
                    <li id="loginLink"><a href="#" onclick="openLoginModal(); return false;">Connexion</a></li>
                    <li id="signupLink"><a href="#" onclick="openSignupModal(); return false;">Inscription</a></li>
                    <li id="adminLink" style="display:none;"><a href="../admin.html">Admin</a></li>
                    <li id="userInfo" style="display:none;"></li>
                </ul>
            </nav>
        </div>
    </header>
    <!-- Header Section End -->

    <!-- Main Content -->
    <div class="container edit-article-container">
        <h1>✏️ Modifier l'article</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Titre de l'article</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($article['title']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" required><?= htmlspecialchars($article['content']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Image actuelle</label>
                <div class="current-image">
                    <img src="../uploads/<?= htmlspecialchars($article['image']) ?>" alt="Article Image">
                </div>
            </div>
            
            <div class="form-group">
                <label for="image">Changer l'image (optionnel)</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small style="color: #999;">Formats acceptés: JPG, PNG, GIF, WebP. Taille max: 5MB</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                <a href="article.php?id=<?= $articleId ?>" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../auth-ui.js"></script>
</body>
</html>
