<?php
require_once __DIR__ . '/../controllers/innovation_controller.php';

$innovationC = new InnovationController();

/* ---- Si un ID est passé en GET → récupérer l'innovation ---- */
$innovation = null;
if (isset($_GET['id'])) {
    $innovation = $innovationC->showInnovation($_GET['id']);
}

/* ---- Si utilisateur confirme la suppression ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === "yes") {
        $innovationC->deleteInnovation($_POST['id']);
        header("Location: read_innovation.php?deleted=1");
        exit();
    } else {
        header("Location: read_innovation.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supprimer une innovation - Dashboard Innovations</title>
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

        /* Confirmation Card */
        .confirmation-card {
            width: 100%;
            max-width: 600px;
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius);
            margin: 40px auto;
            box-shadow: var(--shadow-lg);
            text-align: center;
            animation: fadeIn 0.5s ease;
            border-top: 4px solid var(--danger);
        }

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

        /* Warning Icon */
        .warning-icon {
            width: 80px;
            height: 80px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--danger);
            font-size: 36px;
        }

        /* Confirmation Title */
        .confirmation-title {
            font-size: 1.8rem;
            color: var(--danger);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .confirmation-subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            font-size: 1.1rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Innovation Details */
        .innovation-details {
            background: var(--light);
            border-radius: var(--radius);
            padding: 25px;
            margin: 25px 0;
            text-align: center;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .innovation-id {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .innovation-title {
            font-size: 1.3rem;
            color: var(--gray);
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Buttons */
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
            margin: 0 10px;
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
            transform: translateY(-2px);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
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

        /* Responsive */
        @media (max-width: 768px) {
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

            .confirmation-card {
                padding: 25px;
                margin: 20px auto;
            }

            .header-content h1 {
                font-size: 2rem;
            }

            .confirmation-title {
                font-size: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .btn {
                width: 100%;
                justify-content: center;
                margin: 0;
            }
        }

        /* No Innovation Found */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            max-width: 600px;
            margin: 40px auto;
        }

        .empty-icon {
            font-size: 64px;
            color: var(--border);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Animation for warning icon */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .warning-icon {
            animation: pulse 2s infinite;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <h1><i class="fas fa-trash-alt"></i> Supprimer une Innovation</h1>
                <p>Confirmez la suppression d'une innovation du système</p>
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
                <li><a href="../index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="create_innovation.php"><i class="fas fa-plus-circle"></i> Nouvelle Innovation</a></li>
                <li><a href="read_innovation.php"><i class="fas fa-list"></i> Liste des Innovations</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <?php if ($innovation): ?>
            <!-- Confirmation Card -->
            <div class="confirmation-card">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

                <h2 class="confirmation-title">Supprimer cette innovation ?</h2>
                <p class="confirmation-subtitle">
                    Cette action est irréversible. Toutes les données associées seront définitivement supprimées.
                </p>

                <!-- Innovation Details -->
                <div class="innovation-details">
                    <div class="innovation-id">
                        <i class="fas fa-hashtag"></i> ID : #<?= $innovation['id_innovation'] ?>
                    </div>
                    <div class="innovation-title">
                        <i class="fas fa-lightbulb"></i> Titre : <?= htmlspecialchars($innovation['titre']) ?>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="id" value="<?= $innovation['id_innovation'] ?>">

                    <div class="form-actions">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger"
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette innovation ?\n\nCette action est irréversible.')">
                            <i class="fas fa-trash"></i> Oui, supprimer
                        </button>

                        <button type="submit" name="confirm" value="no" class="btn btn-gray">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- No Innovation Found -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h2 style="color: var(--dark); margin-bottom: 15px;">Aucune innovation trouvée</h2>
                <p style="color: var(--gray); margin-bottom: 25px;">
                    L'innovation que vous essayez de supprimer n'existe pas ou a déjà été supprimée.
                </p>
                <div class="form-actions">
                    <a href="read_innovation.php" class="btn btn-gray">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="dashboard-footer">
        <div class="container">
            <p><i class="fas fa-rocket"></i> &copy; <?= date('Y') ?> InnovDash - Gestion des Innovations. Tous droits
                réservés.</p>
        </div>
    </footer>

    <script>
        // Ajouter des effets d'interaction
        document.addEventListener('DOMContentLoaded', function () {
            // Effet de scroll sur la navigation
            window.addEventListener('scroll', function () {
                const nav = document.querySelector('.main-nav');
                if (window.scrollY > 100) {
                    nav.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
                } else {
                    nav.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                }
            });

            // Animation de la carte
            const confirmationCard = document.querySelector('.confirmation-card');
            if (confirmationCard) {
                confirmationCard.style.opacity = '0';
                confirmationCard.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    confirmationCard.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    confirmationCard.style.opacity = '1';
                    confirmationCard.style.transform = 'translateY(0)';
                }, 100);
            }

            // Effet visuel sur les boutons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-2px)';
                });
                button.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>