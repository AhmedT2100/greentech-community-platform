<?php
require_once __DIR__ . '/../controllers/innovation_controller.php';

// Vérifier si l'ID est passé en paramètre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit();
}

$id = (int) $_GET['id'];
$innovationC = new InnovationController();

// Récupérer toutes les innovations et trouver celle avec l'ID correspondant
$allInnovations = $innovationC->listInnovations();
$innovation = null;

foreach ($allInnovations as $item) {
    if ($item['id_innovation'] == $id) {
        $innovation = $item;
        break;
    }
}

// Vérifier si l'innovation existe
if (!$innovation) {
    header('Location: ../index.php');
    exit();
}

// Calculer la note moyenne
$ratingTotal = isset($innovation['rating_total']) ? floatval($innovation['rating_total']) : 0;
$ratingCount = isset($innovation['rating_count']) ? intval($innovation['rating_count']) : 0;
$avgRating = ($ratingCount > 0 && $ratingTotal > 0) ? round($ratingTotal / $ratingCount, 1) : 0;

// Formater la date
$dateCreation = !empty($innovation['date_creation']) ? date('d/m/Y H:i', strtotime($innovation['date_creation'])) : 'Non spécifiée';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails de l'Innovation - Dashboard Innovations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variables de style unifiées avec l'index */
        :root {
            --primary: #1dbf73;
            --primary-dark: #179e61;
            --primary-light: #e8f5e9;
            --secondary: #6f42c1;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #353535;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border: #dee2e6;
            --white: #ffffff;
            --shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.12);
            --radius: 10px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header moderne */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .header-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Navigation simplifiée */
        .main-nav {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 5px;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
            border-radius: 6px;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 191, 115, 0.2);
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #5a32a3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary-light);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
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

        /* Innovation Details Card */
        .details-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin: 30px 0;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .details-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, rgba(29, 191, 115, 0.1) 100%);
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
        }

        .innovation-icon {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            flex-shrink: 0;
        }

        .header-content-wrapper {
            flex: 1;
            min-width: 300px;
        }

        .innovation-title {
            font-size: 2.2rem;
            color: var(--dark);
            margin: 0 0 12px 0;
            font-weight: 700;
            line-height: 1.2;
        }

        .innovation-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        /* Card Body */
        .card-body {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-content {
            line-height: 1.8;
            color: #333;
            font-size: 16px;
            padding: 15px 0;
        }

        /* Image Container */
        .image-container {
            text-align: center;
            margin: 25px 0;
        }

        .innovation-image {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 3px solid var(--border);
            object-fit: cover;
        }

        /* Rating System */
        .rating-container {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 20px;
            background: var(--light);
            border-radius: var(--radius);
        }

        .stars {
            display: flex;
            gap: 5px;
        }

        .star {
            font-size: 24px;
            color: var(--border);
            transition: transform 0.3s ease;
        }

        .star.active {
            color: var(--warning);
        }

        .rating-info {
            font-size: 1.1rem;
            color: var(--gray);
        }

        .rating-info strong {
            color: var(--dark);
            font-size: 1.3rem;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-card {
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .detail-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--gray);
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .detail-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-info {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
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

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 30px 0;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            margin-top: 50px;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .breadcrumb .separator {
            color: var(--border);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                padding: 0 15px;
            }

            .nav-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px 0;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .details-header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
            }

            .header-content-wrapper {
                min-width: 100%;
            }

            .innovation-meta {
                justify-content: center;
            }

            .card-body {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .header-content h1 {
                font-size: 2rem;
            }

            .innovation-title {
                font-size: 1.8rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .rating-container {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .section-title {
                font-size: 1.2rem;
            }
        }

        /* Page Header */
        .page-header {
            margin: 30px 0;
            text-align: center;
        }

        .page-header h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-lightbulb"></i> Détails de l'Innovation</h1>
                <p>Consultez tous les détails de cette innovation</p>
            </div>
        </div>
    </header>

    <!-- Navigation simplifiée -->
    <nav class="main-nav">
        <div class="container nav-container">
            <div class="logo">
                <i class="fas fa-rocket"></i>
                <span>InnovDash</span>
            </div>
            <ul class="nav-links">
                <li><a href="../index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="create_innovation.php"><i class="fas fa-plus-circle"></i> Nouvelle Innovation</a></li>
                <li><a href="../index.php?tab=innovations"><i class="fas fa-list"></i> Liste des Innovations</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../index.php"><i class="fas fa-home"></i> Dashboard</a>
            <span class="separator">/</span>
            <a href="../index.php?tab=innovations">Innovations</a>
            <span class="separator">/</span>
            <span>Détails #<?= $innovation['id_innovation'] ?></span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-lightbulb"></i> Innovation #<?= $innovation['id_innovation'] ?></h2>
            <p>Informations détaillées et statistiques de cette innovation</p>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="../index.php?tab=innovations" class="btn btn-gray">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
            <a href="update_innovation.php?id=<?= $id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="delete_innovation.php?id=<?= $id ?>" class="btn btn-danger"
                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette innovation ?\n\nCette action est irréversible.');">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>

        <!-- Innovation Details Card -->
        <div class="details-card">
            <!-- En-tête -->
            <div class="details-header">
                <div class="innovation-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="header-content-wrapper">
                    <h1 class="innovation-title"><?= htmlspecialchars($innovation['titre']) ?></h1>
                    <div class="innovation-meta">
                        <span class="meta-item">
                            <i class="fas fa-hashtag"></i>
                            ID: #<?= $innovation['id_innovation'] ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-calendar"></i>
                            Créé le: <?= $dateCreation ?>
                        </span>
                        <?php if (!empty($innovation['votes'])): ?>
                            <span class="meta-item">
                                <i class="fas fa-thumbs-up"></i>
                                Votes: <?= $innovation['votes'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php
                    $statusClass = 'badge-' . ($innovation['statut'] === 'validé' ? 'success' :
                        ($innovation['statut'] === 'en attente' ? 'warning' : 'danger'));
                    ?>
                    <span class="status-badge <?= $statusClass ?>">
                        <i class="fas fa-<?=
                            $innovation['statut'] === 'validé' ? 'check-circle' :
                            ($innovation['statut'] === 'en attente' ? 'clock' : 'times-circle')
                            ?>"></i>
                        <?= htmlspecialchars($innovation['statut']) ?>
                    </span>
                </div>
            </div>

            <!-- Contenu -->
            <div class="card-body">
                <!-- Section Description -->
                <div class="section">
                    <h3 class="section-title">
                        <i class="fas fa-align-left"></i> Description
                    </h3>
                    <div class="section-content">
                        <?= nl2br(htmlspecialchars($innovation['description'])) ?>
                    </div>
                </div>

                <!-- Section Image -->
                <?php if (!empty($innovation['image'])): ?>
                    <div class="section">
                        <h3 class="section-title">
                            <i class="fas fa-image"></i> Image
                        </h3>
                        <div class="image-container">
                            <img src="../uploads/<?= htmlspecialchars($innovation['image']) ?>"
                                alt="<?= htmlspecialchars($innovation['titre']) ?>" class="innovation-image">
                            <div style="margin-top: 10px; color: var(--gray); font-size: 0.9rem;">
                                <i class="fas fa-info-circle"></i> Image associée à l'innovation
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>Aucune image n'est associée à cette innovation.</div>
                    </div>
                <?php endif; ?>

                <!-- Section Rating -->
                <div class="section">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i> Évaluation
                    </h3>
                    <div class="rating-container">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $avgRating ? 'active' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-info">
                            <?php if ($avgRating > 0): ?>
                                Note moyenne : <strong><?= $avgRating ?></strong> / 5
                                (basé sur <?= $ratingCount ?> vote<?= $ratingCount > 1 ? 's' : '' ?>)
                            <?php else: ?>
                                <span style="color: var(--gray); font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Pas encore évalué
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section Détails -->
                <div class="section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i> Informations détaillées
                    </h3>
                    <div class="details-grid">
                        <div class="detail-card">
                            <div class="detail-label">ID Innovation</div>
                            <div class="detail-value">#<?= $innovation['id_innovation'] ?></div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">Statut</div>
                            <div class="detail-value">
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($innovation['statut']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-label">Date de création</div>
                            <div class="detail-value"><?= $dateCreation ?></div>
                        </div>

                        <?php if (!empty($innovation['votes'])): ?>
                            <div class="detail-card">
                                <div class="detail-label">Nombre de votes</div>
                                <div class="detail-value"><?= $innovation['votes'] ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($innovation['rating_count'])): ?>
                            <div class="detail-card">
                                <div class="detail-label">Nombre d'évaluations</div>
                                <div class="detail-value"><?= $ratingCount ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($innovation['rating_total'])): ?>
                            <div class="detail-card">
                                <div class="detail-label">Total des notes</div>
                                <div class="detail-value"><?= $ratingTotal ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($innovation['id_categorie'])): ?>
                            <div class="detail-card">
                                <div class="detail-label">Catégorie</div>
                                <div class="detail-value">#<?= $innovation['id_categorie'] ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer">
        <div class="container">
            <p><i class="fas fa-rocket"></i> &copy; <?= date('Y') ?> InnovDash - Gestion des Innovations. Tous droits
                réservés.</p>
        </div>
    </footer>

    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function () {
            // Animation des étoiles
            const stars = document.querySelectorAll('.star.active');
            stars.forEach((star, index) => {
                setTimeout(() => {
                    star.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        star.style.transform = 'scale(1)';
                    }, 300);
                }, index * 100);
            });

            // Confirmation pour la suppression
            const deleteBtn = document.querySelector('.btn-danger');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function (e) {
                    if (!confirm('Êtes-vous certain de vouloir supprimer cette innovation ?\n\nCette action ne peut pas être annulée.')) {
                        e.preventDefault();
                    }
                });
            }

            // Effet de survol sur les cartes de détails
            const detailCards = document.querySelectorAll('.detail-card');
            detailCards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Animation de la carte principale
            const mainCard = document.querySelector('.details-card');
            mainCard.style.opacity = '0';
            mainCard.style.transform = 'translateY(20px)';
            setTimeout(() => {
                mainCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                mainCard.style.opacity = '1';
                mainCard.style.transform = 'translateY(0)';
            }, 100);

            // Effet de scroll sur la navigation
            window.addEventListener('scroll', function () {
                const nav = document.querySelector('.main-nav');
                if (window.scrollY > 100) {
                    nav.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
                } else {
                    nav.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                }
            });

            // Animation des métadonnées
            const metaItems = document.querySelectorAll('.meta-item');
            metaItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 200 + (index * 50));
                item.style.opacity = '0';
                item.style.transform = 'translateX(-10px)';
            });

            // Copie de l'ID au clic
            const idElement = document.querySelector('.detail-card:first-child .detail-value');
            if (idElement) {
                idElement.style.cursor = 'pointer';
                idElement.title = 'Cliquer pour copier l\'ID';
                idElement.addEventListener('click', function () {
                    const id = this.textContent.replace('#', '');
                    navigator.clipboard.writeText(id).then(() => {
                        const originalText = this.textContent;
                        this.textContent = '✓ Copié !';
                        this.style.color = 'var(--success)';
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.style.color = '';
                        }, 2000);
                    });
                });
            }
        });
    </script>
</body>

</html>