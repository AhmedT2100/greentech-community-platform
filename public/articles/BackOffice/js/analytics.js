/**
 * Analytics Charts JavaScript
 * Handles chart initialization and data display for analytics dashboard
 */

// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeActivityChart();
    initializeActionsChart();
});

// Activity Trend Chart
function initializeActivityChart() {
    const activityData = window.activityData || [];
    if (!activityData.length || !document.getElementById('activityChart')) {
        return;
    }
    
    const dates = activityData.map(d => d.date);
    const counts = activityData.map(d => d.count);

    const ctx1 = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Nombre d\'actions',
                data: counts,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#16a34a',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Actions Distribution Chart
function initializeActionsChart() {
    const actionStats = window.actionStats || [];
    if (!actionStats.length || !document.getElementById('actionsChart')) {
        return;
    }
    
    const actionLabels = actionStats.map(a => {
        const actions = {
            'create': 'Créer',
            'update': 'Modifier',
            'delete': 'Supprimer',
            'resolve': 'Résoudre',
            'view': 'Consulter'
        };
        return actions[a.action] || a.action;
    });
    const actionCounts = actionStats.map(a => a.count);
    const colors = ['#16a34a', '#3b82f6', '#ef4444', '#f59e0b', '#8b5cf6'];

    const ctx2 = document.getElementById('actionsChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: actionLabels,
            datasets: [{
                data: actionCounts,
                backgroundColor: colors.slice(0, actionLabels.length),
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Export for use elsewhere
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeActivityChart,
        initializeActionsChart
    };
}
