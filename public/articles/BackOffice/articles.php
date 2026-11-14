<?php
session_start();
require_once __DIR__ . '/../app/utils/Language.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/ArticleController.php';
require_once __DIR__ . '/../app/utils/ActivityLogger.php';

$db = new Database();
$pdo = $db->getConnection();
$articleCtrl = new ArticleController($pdo);
$logger = new ActivityLogger($pdo);

// Get admin info (only if admin is logged in)
$admin = null;
if (isset($_SESSION['admin_id'])) {
    $adminStmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    try {
        // Get article details for logging
        $articleStmt = $pdo->prepare("SELECT title FROM articles WHERE id = ?");
        $articleStmt->execute([$deleteId]);
        $article = $articleStmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete associated comments first
        $pdo->prepare("DELETE FROM comments WHERE article_id = ?")->execute([$deleteId]);
        // Delete the article
        $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$deleteId]);
        
        // Log activity
        $logger->log($_SESSION['admin_id'], 'delete', 'article', $deleteId, "Suppression d'article", [
            'title' => $article['title'] ?? 'N/A'
        ]);
        
        $success = '✅ Article supprimé avec succès';
    } catch (Exception $e) {
        $error = '❌ Erreur lors de la suppression';
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId = intval($_POST['edit_id']);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if ($title && $content) {
        try {
            $stmt = $pdo->prepare("UPDATE articles SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $editId]);
            
            // Log activity
            $logger->log($_SESSION['admin_id'], 'update', 'article', $editId, "Modification d'article", [
                'title' => $title,
                'content_length' => strlen($content)
            ]);
            
            $success = '✅ Article modifié avec succès';
        } catch (Exception $e) {
            $error = '❌ Erreur lors de la modification';
        }
    } else {
        $error = '❌ Veuillez remplir tous les champs';
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Get articles with search/filter
if ($search || $dateFrom || $dateTo) {
    $query = "SELECT a.*, 
                     GROUP_CONCAT(r.reason SEPARATOR ' | ') as report_reasons,
                     COUNT(r.id) as report_count
              FROM articles a
              LEFT JOIN reports r ON r.article_id = a.id AND r.status = 'pending'
              WHERE 1=1";
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
    
    $query .= " GROUP BY a.id ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT a.*, 
                                GROUP_CONCAT(r.reason SEPARATOR ' | ') as report_reasons,
                                COUNT(r.id) as report_count
                         FROM articles a
                         LEFT JOIN reports r ON r.article_id = a.id AND r.status = 'pending'
                         GROUP BY a.id
                         ORDER BY a.created_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

$editingId = $_GET['edit'] ?? null;
$editingArticle = null;
if ($editingId) {
    $editingArticle = $articleCtrl->get(intval($editingId));
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTech - <?= Language::get('manage_articles_title') ?></title>
    <link rel="stylesheet" href="css/admin.css?v=<?= time(); ?>">
</head>
<body>

<div class="admin-container">
    <!-- Main Content -->
    <main class="main-content" style="margin-left: 0; width: 100%;">
        <a href="dashboard.php" style="display: inline-block; padding: 10px 15px; background: #0f766e; color: white; border-radius: 6px; text-decoration: none; font-size: 16px; transition: background 0.3s; margin: 5px;" onmouseover="this.style.background='#115e59'" onmouseout="this.style.background='#0f766e'" title="Retour au tableau de bord">
            ←
        </a>
        </div>

        <div class="dashboard-content">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Create Article Form -->
            <div class="create-article-container" style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h2 style="margin-bottom: 20px; color: #1e293b;">📝 Créer un nouvel article</h2>
                
                <form id="createArticleForm" class="article-form" style="display: grid; gap: 15px;">
                    <div style="display: grid; gap: 8px;">
                        <label style="font-weight: 600; color: #475569;">Titre *</label>
                        <input type="text" name="title" placeholder="Titre de l'article" required 
                               style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    </div>

                    <div style="display: grid; gap: 8px;">
                        <label style="font-weight: 600; color: #475569;">Contenu *</label>
                        <textarea name="content" placeholder="Contenu de l'article" rows="5" required
                                  style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical;"></textarea>
                    </div>

                    <div style="display: grid; gap: 8px;">
                        <label style="font-weight: 600; color: #475569;">Image *</label>
                        <input type="file" name="image" accept="image/*" required 
                               style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <small style="color: #64748b;">Formats acceptés: JPG, PNG, GIF, WebP (max 5MB)</small>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" id="createArticleBtn" class="btn-primary" style="padding: 12px 24px; cursor: pointer;">
                            ✅ Créer l'article
                        </button>
                        <button type="reset" class="btn-secondary" style="padding: 12px 24px; cursor: pointer; background: #6b7280; color: white; border: none; border-radius: 6px;">
                            🔄 Réinitialiser
                        </button>
                    </div>

                    <div id="createArticleMessage" style="display: none; padding: 12px; border-radius: 6px; font-weight: 500;"></div>
                </form>
            </div>

            <!-- Search & Filter Section -->
            <div class="search-filter-container">
                <form method="GET" class="search-filter-form">
                    <div class="filter-row">
                        <input type="text" name="search" placeholder="🔍 Rechercher par titre ou contenu..." 
                               value="<?= htmlspecialchars($search) ?>" class="search-input">
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Du:</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="filter-group">
                            <label>Au:</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <button type="submit" class="btn-primary">🔎 Filtrer</button>
                        <a href="articles.php" class="btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 8px;">↺ Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Results count -->
            <div class="results-info">
                📊 <strong><?= count($articles) ?></strong> article(s) trouvé(s)
                <?php if ($search): ?>
                    - Recherche: "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
            </div>

            <?php if ($editingArticle): ?>
                <!-- Edit Article Form -->
                <div class="edit-form-container">
                    <h2>✏️ Modifier l'article</h2>
                    <form method="POST" class="article-form">
                        <input type="hidden" name="edit_id" value="<?= $editingArticle['id'] ?>">
                        
                        <div class="form-group">
                            <label>Titre</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($editingArticle['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Contenu</label>
                            <textarea name="content" required><?= htmlspecialchars($editingArticle['content']) ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">💾 Enregistrer</button>
                            <a href="articles.php" class="btn-secondary">❌ Annuler</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Articles Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Date</th>
                                <th>Likes</th>
                                <th>Commentaires</th>
                                <th>Signalements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($articles)): ?>
                                <?php foreach ($articles as $article):
                                    // Get comment count
                                    $commentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE article_id = ?");
                                    $commentStmt->execute([$article['id']]);
                                    $commentCount = $commentStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                                    $hasReports = !empty($article['report_reasons']);
                                ?>
                                    <tr style="<?= $hasReports ? 'background: #fef2f2; border-left: 3px solid #ef4444;' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars(substr($article['title'], 0, 40)) ?></strong>
                                            <?php if (strlen($article['title']) > 40): ?>
                                                <span>...</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>Utilisateur</td>
                                        <td><?= getRelativeDate($article['created_at']) ?></td>
                                        <td>
                                            <span class="badge badge-success">❤️ <?= $article['likes'] ?? 0 ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">💬 <?= $commentCount ?></span>
                                        </td>
                                        <td>
                                            <?php if ($hasReports): ?>
                                                <span class="badge badge-danger" title="<?= htmlspecialchars($article['report_reasons']) ?>">
                                                    🚩 <?= $article['report_count'] ?> rapport(s)
                                                </span>
                                                <small style="display: block; margin-top: 5px; color: #dc2626; font-weight: 500;">
                                                    <?= htmlspecialchars(substr($article['report_reasons'], 0, 40)) ?><?= strlen($article['report_reasons']) > 40 ? '...' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons-table">
                                                <a href="?edit=<?= $article['id'] ?>" class="btn-small btn-edit">✏️ Modifier</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr?')">
                                                    <input type="hidden" name="delete_id" value="<?= $article['id'] ?>">
                                                    <button type="submit" class="btn-small btn-delete">🗑️ Supprimer</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                        📭 Aucun article trouvé
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Handle article creation via AJAX
document.getElementById('createArticleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const title = formData.get('title').trim();
    const content = formData.get('content').trim();
    const image = formData.get('image');
    const messageDiv = document.getElementById('createArticleMessage');
    const btn = document.getElementById('createArticleBtn');

    // Validation
    if (!title) {
        showMessage('Le titre est requis', 'error');
        return;
    }
    if (!content) {
        showMessage('Le contenu est requis', 'error');
        return;
    }
    if (!image || image.size === 0) {
        showMessage('Une image est requise', 'error');
        return;
    }
    if (image.size > 5 * 1024 * 1024) {
        showMessage('La taille de l\'image ne doit pas dépasser 5MB', 'error');
        return;
    }

    btn.disabled = true;
    showMessage('Création en cours...', 'info');

    // Add action to FormData
    formData.append('action', 'create_article');

    fetch('api_admin_content.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('✅ Article créé avec succès!', 'success');
            document.getElementById('createArticleForm').reset();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage('❌ ' + (data.error || 'Erreur lors de la création'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('❌ Erreur de connexion', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });

    function showMessage(msg, type) {
        messageDiv.textContent = msg;
        messageDiv.style.display = 'block';
        messageDiv.style.backgroundColor = type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1';
        messageDiv.style.color = type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460';
        messageDiv.style.borderLeft = '4px solid ' + (type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8');
    }
});
</script>
</body>
</html>
