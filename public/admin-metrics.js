/**
 * Script pour les métriques avancées
 */

// Variables globales pour les graphiques
let userGrowthChart = null;
let sessionStatsChart = null;
let deviceStatsChart = null;

// Initialiser les dates par défaut
document.addEventListener('DOMContentLoaded', function () {
    const today = new Date().toISOString().split('T')[0];
    const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    if (document.getElementById('reportStartDate')) {
        document.getElementById('reportStartDate').value = monthAgo;
        document.getElementById('reportEndDate').value = today;
    }

    // Charger les métriques quand l'onglet est activé
    const metricsTab = document.getElementById('metrics-tab');
    if (metricsTab) {
        metricsTab.addEventListener('shown.bs.tab', function () {
            loadAllMetrics();
        });
    }
});

/**
 * Charger toutes les métriques
 */
function loadAllMetrics() {
    loadDashboardMetrics();
    loadUserGrowth(30);
    loadSessionStats(7);
    loadKPIProgress();
    loadDeviceStats();
    loadEmailStats();
    loadGeolocationStats();
    loadRealtimeActivity();

    // Actualiser l'activité en temps réel toutes les 30 secondes
    setInterval(loadRealtimeActivity, 30000);
}

/**
 * Charger les métriques du dashboard
 */
async function loadDashboardMetrics() {
    try {
        const response = await fetch('metrics_api.php?action=getDashboardMetrics');
        const data = await response.json();

        if (data.success) {
            const m = data.metrics;

            // Mettre à jour les cartes de métriques
            document.getElementById('activeUsers7d').textContent = m.activeUsers7d;
            document.getElementById('sessionsToday').textContent = m.sessionsToday;
            document.getElementById('avgSessionDuration').textContent = m.avgSessionDuration;
            document.getElementById('retentionRate').textContent = m.retentionRate + '%';
        }
    } catch (error) {
        console.error('Erreur lors du chargement des métriques:', error);
    }
}

/**
 * Charger la croissance des utilisateurs
 */
async function loadUserGrowth(days) {
    try {
        const response = await fetch(`metrics_api.php?action=getUserGrowth&days=${days}`);
        const result = await response.json();

        if (result.success) {
            const data = result.data;
            const labels = data.map(d => d.date);
            const newUsers = data.map(d => parseInt(d.new_users));
            const cumulativeUsers = data.map(d => parseInt(d.cumulative_users));

            const ctx = document.getElementById('userGrowthChart');

            // Détruire le graphique existant
            if (userGrowthChart) {
                userGrowthChart.destroy();
            }

            // Créer le nouveau graphique
            userGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Nouveaux utilisateurs',
                            data: newUsers,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Total cumulé',
                            data: cumulativeUsers,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement de la croissance:', error);
    }
}

/**
 * Charger les statistiques de sessions
 */
async function loadSessionStats(days) {
    try {
        const response = await fetch(`metrics_api.php?action=getSessionStats&days=${days}`);
        const result = await response.json();

        if (result.success) {
            const data = result.data;
            const labels = data.map(d => d.date);
            const sessions = data.map(d => parseInt(d.total_sessions));
            const uniqueUsers = data.map(d => parseInt(d.unique_users));

            const ctx = document.getElementById('sessionStatsChart');

            // Détruire le graphique existant
            if (sessionStatsChart) {
                sessionStatsChart.destroy();
            }

            // Créer le nouveau graphique
            sessionStatsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sessions totales',
                            data: sessions,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        },
                        {
                            label: 'Utilisateurs uniques',
                            data: uniqueUsers,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des sessions:', error);
    }
}

/**
 * Charger la progression des KPIs
 */
async function loadKPIProgress() {
    try {
        const response = await fetch('metrics_api.php?action=getKPIProgress');
        const result = await response.json();

        if (result.success) {
            const container = document.getElementById('kpiContainer');
            container.innerHTML = '';

            result.data.forEach(kpi => {
                const progressColor = kpi.progress >= 100 ? 'success' : kpi.progress >= 75 ? 'info' : kpi.progress >= 50 ? 'warning' : 'danger';

                const kpiHtml = `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>${kpi.kpi_name}</strong>
                            <span class="badge bg-${progressColor}">${kpi.progress}%</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-${progressColor}" role="progressbar" 
                                 style="width: ${Math.min(kpi.progress, 100)}%;" 
                                 aria-valuenow="${kpi.progress}" aria-valuemin="0" aria-valuemax="100">
                                ${kpi.current_value} / ${kpi.target_value}
                            </div>
                        </div>
                        <small class="text-muted">Période: ${kpi.start_date} - ${kpi.end_date}</small>
                    </div>
                `;

                container.innerHTML += kpiHtml;
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des KPIs:', error);
    }
}

/**
 * Charger les statistiques par appareil
 */
async function loadDeviceStats() {
    try {
        const response = await fetch('metrics_api.php?action=getDeviceStats');
        const result = await response.json();

        if (result.success) {
            const data = result.data;
            const labels = data.map(d => d.device_type || 'Unknown');
            const sessions = data.map(d => parseInt(d.total_sessions));

            const ctx = document.getElementById('deviceStatsChart');

            // Détruire le graphique existant
            if (deviceStatsChart) {
                deviceStatsChart.destroy();
            }

            // Créer le nouveau graphique
            deviceStatsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: sessions,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats par appareil:', error);
    }
}

/**
 * Charger les statistiques d'emails
 */
async function loadEmailStats() {
    try {
        const response = await fetch('metrics_api.php?action=getEmailStats');
        const result = await response.json();

        if (result.success) {
            const tbody = document.querySelector('#emailStatsTable tbody');
            tbody.innerHTML = '';

            result.data.forEach(stat => {
                const row = `
                    <tr>
                        <td>${stat.email_type}</td>
                        <td>${stat.total_sent}</td>
                        <td>${stat.total_opened}</td>
                        <td>
                            <span class="badge bg-${stat.open_rate >= 50 ? 'success' : stat.open_rate >= 25 ? 'warning' : 'danger'}">
                                ${stat.open_rate}%
                            </span>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucune donnée disponible</td></tr>';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats emails:', error);
    }
}

/**
 * Charger les statistiques de géolocalisation
 */
async function loadGeolocationStats() {
    try {
        const response = await fetch('metrics_api.php?action=getGeolocationStats');
        const result = await response.json();

        if (result.success) {
            const tbody = document.querySelector('#geoStatsTable tbody');
            tbody.innerHTML = '';

            result.data.forEach(stat => {
                const row = `
                    <tr>
                        <td><i class="fas fa-flag"></i> ${stat.country || 'Inconnu'}</td>
                        <td>${stat.city || '-'}</td>
                        <td>${stat.total_sessions}</td>
                        <td>${stat.unique_users}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucune donnée disponible</td></tr>';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats géo:', error);
    }
}

/**
 * Charger l'activité en temps réel
 */
async function loadRealtimeActivity() {
    try {
        const response = await fetch('metrics_api.php?action=getRealtimeActivity');
        const result = await response.json();

        if (result.success) {
            const container = document.getElementById('realtimeActivity');
            const onlineCount = document.getElementById('onlineUsersCount');

            onlineCount.textContent = result.data.onlineUsers;

            container.innerHTML = '';

            result.data.events.forEach(event => {
                const eventHtml = `
                    <div class="alert alert-light border-start border-3 border-primary mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <i class="fas fa-${getEventIcon(event.event_type)}"></i>
                                <strong>${event.nom || 'Anonyme'} ${event.prenom || ''}</strong>
                                <span class="text-muted">- ${event.event_type}</span>
                            </div>
                            <small class="text-muted">${formatDateTime(event.created_at)}</small>
                        </div>
                    </div>
                `;
                container.innerHTML += eventHtml;
            });

            if (result.data.events.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">Aucune activité récente</p>';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'activité:', error);
    }
}

/**
 * Exporter un rapport
 */
async function exportReport() {
    const reportType = document.getElementById('reportType').value;
    const startDate = document.getElementById('reportStartDate').value;
    const endDate = document.getElementById('reportEndDate').value;

    if (!startDate || !endDate) {
        alert('Veuillez sélectionner les dates de début et de fin');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'exportReport');
        formData.append('reportType', reportType);
        formData.append('startDate', startDate);
        formData.append('endDate', endDate);

        const response = await fetch('metrics_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Télécharger le fichier
            window.location.href = result.downloadUrl;
            alert('Rapport exporté avec succès !');
        } else {
            alert('Erreur lors de l\'export: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur lors de l\'export:', error);
        alert('Erreur lors de l\'export du rapport');
    }
}

/**
 * Obtenir l'icône pour un type d'événement
 */
function getEventIcon(eventType) {
    const icons = {
        'login': 'sign-in-alt',
        'logout': 'sign-out-alt',
        'login_failed': 'exclamation-triangle',
        'signup': 'user-plus',
        'profile_update': 'user-edit',
        'password_change': 'key',
        'innovation_post': 'lightbulb',
        'innovation_delete': 'trash'
    };

    return icons[eventType] || 'circle';
}

/**
 * Formater une date/heure
 */
function formatDateTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // différence en secondes

    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;

    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Fonction pour afficher un onglet (appelée depuis la sidebar)
 */
function showTab(tabName) {
    const tab = document.getElementById(tabName + '-tab');
    if (tab) {
        const bsTab = new bootstrap.Tab(tab);
        bsTab.show();
    }
}
