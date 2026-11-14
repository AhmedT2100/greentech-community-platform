<?php
// admin.php - Vue du panneau d'administration (MVC)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTechHub | Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../../dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-leaf"></i>
                <h2>GreenTechHub</h2>
            </div>
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="web.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Tableau de Bord</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">
                            <i class="fas fa-users-cog"></i>
                            <span>Administration</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="readUser.php">
                            <i class="fas fa-users"></i>
                            <span>Gestion Utilisateurs</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Administration GreenTechHub</h1>
                <div class="user-actions">
                    <span class="badge bg-primary">Admin</span>
                    <button class="btn btn-outline-secondary" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </button>
                </div>
            </div>
            <!-- Statistiques Admin -->
            <div class="stats-grid">
                <div class="stat-card stat-1">
                    <div class="stat-info">
                        <h3>Utilisateurs Totaux</h3>
                        <div class="stat-value" id="totalUsers">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-users"></i> Utilisateurs actifs
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card stat-2">
                    <div class="stat-info">
                        <h3>Innovations Postées</h3>
                        <div class="stat-value" id="totalInnovations">0</div>
                        <div class="stat-change positive">
                            <i class="fas fa-lightbulb"></i> Innovations totales
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                </div>
                <div class="stat-card stat-3">
                    <div class="stat-info">
                        <h3>Administrateurs</h3>
                        <div class="stat-value" id="totalAdmins">0</div>
                        <div class="stat-change">
                            <i class="fas fa-shield-alt"></i> Accès admin
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
            <!-- Gestion des Utilisateurs -->
            <div class="chart-card mb-4">
                <div class="chart-header">
                    <h3>Gestion des Utilisateurs</h3>
                    <button class="btn btn-primary btn-sm" onclick="refreshUsers()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTable">
                            <!-- Les données seront chargées ici -->
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Historique des Innovations -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Historique des Innovations</h3>
                    <button class="btn btn-primary btn-sm" onclick="refreshHistory()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID Historique</th>
                                <th>Utilisateur</th>
                                <th>ID Innovation</th>
                                <th>Date de Publication</th>
                            </tr>
                        </thead>
                        <tbody id="historyTable">
                            <!-- Les données seront chargées ici -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal pour modifier un utilisateur -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId">
                        <div class="mb-3">
                            <label for="editUserName" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="editUserName">
                        </div>
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editUserEmail">
                        </div>
                        <div class="mb-3">
                            <label for="editUserRole" class="form-label">Rôle</label>
                            <select class="form-control" id="editUserRole">
                                <option value="user">Utilisateur</option>
                                <option value="moderateur">Modérateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../admin.js"></script>
</body>
</html>
