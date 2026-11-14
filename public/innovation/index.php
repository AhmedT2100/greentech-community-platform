<?php
// index.php – Version avec template GreenTechHub

require_once __DIR__ . '/controllers/innovation_controller.php';
require_once __DIR__ . '/../categorie/controllers/categorie_controller.php';
require_once __DIR__ . '/../categorie/models/categorie.php';

$innovationC = new InnovationController();
$categorieC = new CategorieController();
$listeCategories = $categorieC->listCategories();

// Filtres / recherche / tri / pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statutSel = $_GET['statut'] ?? '';
$sortColumn = $_GET['sort'] ?? '';
$sortDir = strtoupper($_GET['dir'] ?? 'ASC');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 6;

$allowedSort = ['titre', 'date_creation', 'votes', 'rating', 'statut'];

$allInnovations = $innovationC->listInnovations();

$filtered = array_filter($allInnovations, function ($row) use ($search, $statutSel) {
    $ok = true;
    if ($search !== '') {
        $ok = $ok && (
            stripos($row['titre'], $search) !== false ||
            stripos($row['description'], $search) !== false
        );
    }
    if ($statutSel !== '') {
        $ok = $ok && ($row['statut'] === $statutSel);
    }
    return $ok;
});

if (in_array($sortColumn, $allowedSort) && !empty($sortColumn)) {
    usort($filtered, function ($a, $b) use ($sortColumn, $sortDir) {
        $va = $a[$sortColumn] ?? '';
        $vb = $b[$sortColumn] ?? '';

        if ($sortColumn === 'date_creation') {
            $va = !empty($va) ? strtotime($va) : 0;
            $vb = !empty($vb) ? strtotime($vb) : 0;
        }

        if ($sortColumn === 'votes' || $sortColumn === 'rating') {
            if ($sortColumn === 'rating') {
                $countA = isset($a['rating_count']) ? intval($a['rating_count']) : 0;
                $totalA = isset($a['rating_total']) ? floatval($a['rating_total']) : 0;
                $countB = isset($b['rating_count']) ? intval($b['rating_count']) : 0;
                $totalB = isset($b['rating_total']) ? floatval($b['rating_total']) : 0;
                $va = ($countA > 0 && $totalA > 0) ? ($totalA / $countA) : 0;
                $vb = ($countB > 0 && $totalB > 0) ? ($totalB / $countB) : 0;
            } else {
                $va = intval($va);
                $vb = intval($vb);
            }
        }

        if (is_string($va) && is_string($vb)) {
            $cmp = strcasecmp($va, $vb);
        } else {
            $cmp = ($va < $vb) ? -1 : (($va > $vb) ? 1 : 0);
        }

        return $sortDir === 'DESC' ? -$cmp : $cmp;
    });
}

$total = count($filtered);
$pages = max(1, (int) ceil($total / $limit));
if ($page > $pages)
    $page = $pages;
$offset = ($page - 1) * $limit;
$liste = array_slice($filtered, $offset, $limit);

$error = '';
$success = '';

// Fonction pour générer l'ID de catégorie automatiquement
function generateNextCategorieId($existingCategories)
{
    if (empty($existingCategories)) {
        return 1;
    }

    $maxId = 0;
    foreach ($existingCategories as $cat) {
        $id = intval($cat['id_categorie'] ?? 0);
        if ($id > $maxId) {
            $maxId = $id;
        }
    }

    return $maxId + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_categorie':
                if (!empty($_POST['nom'])) {
                    // Génération automatique de l'ID
                    $nextId = generateNextCategorieId($listeCategories);
                    $categorie = new Categorie($nextId, $_POST['nom'], $_POST['description'] ?? '');
                    $categorieC->addCategorie($categorie);
                    $success = "Catégorie ajoutée avec succès ! (ID: $nextId)";
                    $listeCategories = $categorieC->listCategories();
                } else {
                    $error = "Le nom est obligatoire !";
                }
                break;
            case 'update_categorie':
                if (!empty($_POST['id']) && !empty($_POST['nom'])) {
                    $categorie = new Categorie($_POST['id'], $_POST['nom'], $_POST['description'] ?? '');
                    $categorieC->updateCategorie($categorie);
                    $success = "Catégorie modifiée avec succès !";
                    $listeCategories = $categorieC->listCategories();
                } else {
                    $error = "Le nom est obligatoire !";
                }
                break;
        }
    }
}

if (isset($_GET['delete_categorie'])) {
    $categorieC->deleteCategorie($_GET['delete_categorie']);
    $success = "Catégorie supprimée avec succès !";
    $listeCategories = $categorieC->listCategories();
}

$categorieToEdit = null;
if (isset($_GET['edit_categorie'])) {
    $categorieToEdit = $categorieC->getCategorieById($_GET['edit_categorie']);
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'innovations';

// Config Template
$pageTitle = 'InnovDash - Dashboard Innovations';
$activePage = 'innovations';
$basePath = '..';
$extraCss = '<link href="../css/innovation.css" rel="stylesheet">';
include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1><i class="fas fa-lightbulb fa-beat"></i> InnovDash</h1>
        <p>La plateforme complète pour gérer, suivre et valoriser vos idées innovantes</p>
        <a href="view/create_innovation.php" class="btn"><i class="fas fa-plus-circle"></i> Créer une innovation</a>
    </div>
</section>

<!-- Main Content -->
<main id="content">
    <div class="container">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button onclick="this.parentElement.style.display='none'"
                    style="margin-left:20px;background:none;border:none;color:#155724;cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button onclick="this.parentElement.style.display='none'"
                    style="margin-left:20px;background:none;border:none;color:#721c24;cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-lightbulb"></i>
                <h3><?= count($allInnovations) ?></h3>
                <p>Total Innovations</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?= count(array_filter($allInnovations, fn($i) => $i['statut'] === 'validé')) ?></h3>
                <p>Innovations Validées</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?= count(array_filter($allInnovations, fn($i) => $i['statut'] === 'en attente')) ?></h3>
                <p>En Attente</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-tags"></i>
                <h3><?= count($listeCategories) ?></h3>
                <p>Catégories</p>
            </div>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h3>Gestion des Innovations</h3>
            <p>Explorez, gérez et analysez toutes vos innovations en un seul endroit</p>
        </div>

        <!-- Contenu principal avec onglets -->
        <div class="main-tabs">
            <div class="tabs-header">
                <button class="tab-btn <?= $activeTab === 'innovations' ? 'active' : '' ?>"
                    onclick="location.href='?tab=innovations'">
                    <i class="fas fa-lightbulb"></i> Innovations
                    <span class="badge"><?= count($filtered) ?></span>
                </button>
                <button class="tab-btn <?= $activeTab === 'categories' ? 'active' : '' ?>"
                    onclick="location.href='?tab=categories'">
                    <i class="fas fa-tags"></i> Catégories
                    <span class="badge"><?= count($listeCategories) ?></span>
                </button>
            </div>

            <!-- Onglet Innovations - CHAMPS DE FILTRAGE AVEC TEXTE VISIBLE -->
            <div class="tab-content <?= $activeTab === 'innovations' ? 'active' : '' ?>">
                <div class="filters">
                    <form method="GET">
                        <input type="hidden" name="tab" value="innovations">
                        <div class="filter-grid">
                            <!-- Recherche - Prend 2 colonnes -->
                            <div class="search-input">
                                <label class="filter-label">Recherche</label>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Rechercher par titre ou description..."
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>

                            <!-- Statut -->
                            <div>
                                <label class="filter-label">Statut</label>
                                <select name="statut" class="form-control">
                                    <option value="">Tous les statuts</option>
                                    <option value="validé" <?= $statutSel === 'validé' ? 'selected' : '' ?>>Validé</option>
                                    <option value="en attente" <?= $statutSel === 'en attente' ? 'selected' : '' ?>>En
                                        attente</option>
                                </select>
                            </div>

                            <!-- Trier par -->
                            <div>
                                <label class="filter-label">Trier par</label>
                                <select name="sort" class="form-control">
                                    <option value="">Défaut</option>
                                    <option value="titre" <?= $sortColumn === 'titre' ? 'selected' : '' ?>>Titre</option>
                                    <option value="rating" <?= $sortColumn === 'rating' ? 'selected' : '' ?>>Note moyenne
                                    </option>
                                    <option value="votes" <?= $sortColumn === 'votes' ? 'selected' : '' ?>>Votes</option>
                                </select>
                            </div>

                            <!-- Ordre -->
                            <div>
                                <label class="filter-label">Ordre</label>
                                <select name="dir" class="form-control">
                                    <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>Croissant</option>
                                    <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>Décroissant</option>
                                </select>
                            </div>

                            <!-- Boutons - Prend 2 colonnes -->
                            <div style="grid-column: span 2; display:flex; align-items:end; gap:10px;">
                                <button type="submit" class="btn btn-filter" style="flex:1;">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                                <a href="?tab=innovations" class="btn-reset">
                                    <i class="fas fa-undo"></i> Réinit.
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (!empty($liste)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Description</th>
                                    <th>Image</th>
                                    <th>Statut</th>
                                    <th>Note</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liste as $row):
                                    $ratingTotal = $row['rating_total'] ?? 0;
                                    $ratingCount = $row['rating_count'] ?? 0;
                                    $avgRating = ($ratingCount > 0) ? round($ratingTotal / $ratingCount, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_innovation'] ?></strong></td>
                                        <td><strong><?= htmlspecialchars($row['titre']) ?></strong></td>
                                        <td><?= htmlspecialchars(substr($row['description'], 0, 120)) ?><?= strlen($row['description']) > 120 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['image'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($row['image']) ?>" width="100"
                                                    style="border-radius:12px;object-fit:cover;height:70px;"
                                                    alt="<?= htmlspecialchars($row['titre']) ?>">
                                            <?php else: ?>
                                                <div
                                                    style="width:100px;height:70px;background:var(--primary-light);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-image" style="font-size:2rem;color:var(--gray);"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-<?= $row['statut'] === 'validé' ? 'success' : ($row['statut'] === 'en attente' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst(htmlspecialchars($row['statut'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="color:#dee2e6; font-size:1.1rem; white-space:nowrap;">
                                                <?php for ($i = 1; $i <= 5; $i++):
                                                    $voteUrl = "vote.php?id=" . $row['id_innovation'] . "&stars=" . $i .
                                                        "&tab=innovations" .
                                                        "&search=" . urlencode($search) .
                                                        "&statut=" . urlencode($statutSel) .
                                                        "&sort=" . urlencode($sortColumn) .
                                                        "&dir=" . urlencode($sortDir) .
                                                        "&page=" . $page;
                                                    ?>
                                                    <a href="<?= $voteUrl ?>" style="text-decoration:none;">
                                                        <span
                                                            style="color: <?= $i <= $avgRating ? '#ffc107' : '#e4e4e4' ?>; cursor:pointer;"
                                                            title="Voter <?= $i ?> étoiles">★</span>
                                                    </a>
                                                <?php endfor; ?>
                                            </div>
                                            <small style="color:var(--gray); font-size:0.8rem;"><?= $avgRating ?>/5
                                                (<?= $ratingCount ?>)</small>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="view/read_innovation.php?id=<?= $row['id_innovation'] ?>"
                                                    class="btn-view" title="Voir détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="view/update_innovation.php?id=<?= $row['id_innovation'] ?>"
                                                    class="btn-edit" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="view/delete_innovation.php?id=<?= $row['id_innovation'] ?>"
                                                    class="btn-delete" title="Supprimer"
                                                    onclick="return confirm('Supprimer cette innovation ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?tab=innovations&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statutSel) ?>&sort=<?= urlencode($sortColumn) ?>&dir=<?= urlencode($sortDir) ?>"
                                    class="page-link"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($pages, $page + 2);

                            for ($p = $start; $p <= $end; $p++):
                                ?>
                                <a href="?tab=innovations&page=<?= $p ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statutSel) ?>&sort=<?= urlencode($sortColumn) ?>&dir=<?= urlencode($sortDir) ?>"
                                    class="page-link <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $pages): ?>
                                <a href="?tab=innovations&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&statut=<?= urlencode($statutSel) ?>&sort=<?= urlencode($sortColumn) ?>&dir=<?= urlencode($sortDir) ?>"
                                    class="page-link"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 20px; color: var(--gray);">
                        <small>
                            Affichage de <?= min($offset + 1, $total) ?> à <?= min($offset + count($liste), $total) ?>
                            sur <?= $total ?> innovation<?= $total > 1 ? 's' : '' ?>
                        </small>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Aucune innovation trouvée</h3>
                        <p>Essayez de modifier vos critères de recherche ou créez votre première innovation.</p>
                        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 30px;">
                            <a href="?tab=innovations" class="btn-reset" style="text-align: center;">
                                <i class="fas fa-undo"></i> Réinit.
                            </a>
                            <a href="view/create_innovation.php" class="btn"
                                style="text-align: center; padding: 8px 20px; font-size: 0.9rem;">
                                <i class="fas fa-plus-circle"></i> Créer
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Onglet Catégories -->
            <div class="tab-content <?= $activeTab === 'categories' ? 'active' : '' ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:50px;">
                    <div class="modern-card">
                        <h2 style="margin-bottom:30px;color:var(--dark);">
                            <i class="fas fa-<?= $categorieToEdit ? 'edit' : 'plus-circle' ?>"></i>
                            <?= $categorieToEdit ? 'Modifier la catégorie' : 'Ajouter une nouvelle catégorie' ?>
                            <?php if (!$categorieToEdit): ?>
                                <span class="auto-id-info">ID auto-généré</span>
                            <?php endif; ?>
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action"
                                value="<?= $categorieToEdit ? 'update_categorie' : 'add_categorie' ?>">
                            <?php if ($categorieToEdit): ?>
                                <input type="hidden" name="id" value="<?= $categorieToEdit['id_categorie'] ?>">
                                <div
                                    style="margin-bottom:20px;padding:12px;background:#f8f9fa;border-radius:var(--radius);">
                                    <strong>ID de la catégorie :</strong> #<?= $categorieToEdit['id_categorie'] ?>
                                </div>
                            <?php else: ?>
                                <?php $nextId = generateNextCategorieId($listeCategories); ?>
                                <div
                                    style="margin-bottom:20px;padding:12px;background:var(--primary-light);border-radius:var(--radius);color:var(--primary);">
                                    <strong><i class="fas fa-info-circle"></i> ID qui sera généré :</strong>
                                    #<?= $nextId ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-bottom:25px;">
                                <label style="display:block;margin-bottom:10px;font-weight:600;color:var(--dark);">Nom
                                    de la catégorie *</label>
                                <input type="text" name="nom" required class="form-control"
                                    value="<?= $categorieToEdit ? htmlspecialchars($categorieToEdit['nom']) : '' ?>">
                            </div>
                            <div style="margin-bottom:30px;">
                                <label
                                    style="display:block;margin-bottom:10px;font-weight:600;color:var(--dark);">Description
                                    (optionnel)</label>
                                <textarea name="description" rows="5" class="form-control"
                                    placeholder="Décrivez cette catégorie..."><?= $categorieToEdit ? htmlspecialchars($categorieToEdit['description'] ?? '') : '' ?></textarea>
                            </div>
                            <div>
                                <button type="submit" class="btn" style="padding:14px 40px;font-size:1.1rem;">
                                    <i class="fas fa-save"></i>
                                    <?= $categorieToEdit ? 'Enregistrer les modifications' : 'Ajouter la catégorie' ?>
                                </button>
                                <?php if ($categorieToEdit): ?>
                                    <a href="?tab=categories" class="btn-cancel" style="margin-left:15px;">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                            <h2 style="color:var(--dark);"><i class="fas fa-list"></i> Liste des catégories</h2>
                            <div class="action-btns">
                                <a href="?tab=categories" class="btn-view" title="Actualiser">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($listeCategories)): ?>
                            <div style="max-height:500px;overflow-y:auto;">
                                <table class="table">
                                    <thead style="position:sticky;top:0;">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listeCategories as $cat): ?>
                                            <tr>
                                                <td><strong>#<?= $cat['id_categorie'] ?></strong></td>
                                                <td><strong><?= htmlspecialchars($cat['nom']) ?></strong></td>
                                                <td><?= !empty($cat['description']) ? htmlspecialchars($cat['description']) : '<em style="color:var(--gray);">Aucune description</em>' ?>
                                                </td>
                                                <td>
                                                    <div class="action-btns">
                                                        <a href="?tab=categories&edit_categorie=<?= $cat['id_categorie'] ?>"
                                                            class="btn-edit" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?tab=categories&delete_categorie=<?= $cat['id_categorie'] ?>"
                                                            class="btn-delete" title="Supprimer"
                                                            onclick="return confirm('Supprimer cette catégorie ? Toutes les innovations associées pourraient être affectées.')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <h3>Aucune catégorie</h3>
                                <p>Commencez par en créer une à gauche !</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Confirmation avant suppression
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                if (!confirm("Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.")) {
                    e.preventDefault();
                }
            });
        });

        // Animation pour les cartes de statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });

    // Auto-submit du formulaire de filtrage au changement
    const filterSelects = document.querySelectorAll('select[name="statut"], select[name="sort"], select[name="dir"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function () {
            this.form.submit();
        });
    });
</script>

<?php include '../includes/footer.php'; ?>