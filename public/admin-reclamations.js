// admin-reclamations.js - Admin reclamation management functions

let allReclamationsData = [];
let currentReclamationPage = 1;
let reclamationLimit = 50;
let totalReclamations = 0;
let currentSortColumn = 'date';
let currentSortDirection = 'desc';

// Load all reclamations
async function loadAllReclamations() {
    try {
        const search = document.getElementById('searchReclamations')?.value || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const priority = document.getElementById('filterPriority')?.value || '';

        let searchQuery = search;
        if (status) searchQuery = status;
        if (priority) searchQuery = priority;

        const response = await fetch(`reclamations_api.php?action=get_all_reclamations&page=${currentReclamationPage}&limit=${reclamationLimit}&search=${encodeURIComponent(searchQuery)}`);
        const data = await response.json();

        if (data.success) {
            allReclamationsData = data.reclamations;
            totalReclamations = data.total;
            displayReclamations(data.reclamations);
            updateReclamationPagination();
        } else {
            console.error('Error loading reclamations:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function displayReclamations(reclamations) {
    const tbody = document.getElementById('reclamationsTable');
    if (!tbody) return;

    if (reclamations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Aucune réclamation trouvée</td></tr>';
        return;
    }

    tbody.innerHTML = reclamations.map(rec => {
        const priorityBadge = getPriorityBadgeClass(rec.priority);
        const statusBadge = getStatusBadgeClass(rec.statut);

        return `
            <tr>
                <td>${rec.id_reclamation}</td>
                <td>
                    <strong>${rec.sujet}</strong><br>
                    <small class="text-muted">${rec.description.substring(0, 50)}${rec.description.length > 50 ? '...' : ''}</small>
                </td>
                <td>
                    ${rec.user_name}<br>
                    <small class="text-muted">${rec.user_email}</small>
                </td>
                <td><span class="badge ${priorityBadge}">${getPriorityLabel(rec.priority)}</span></td>
                <td><span class="badge ${statusBadge}">${rec.statut}</span></td>
                <td><small>${formatDateTime(rec.date_creation)}</small></td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewReclamationAdmin(${rec.id_reclamation})" title="Voir">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="respondToReclamation(${rec.id_reclamation})" title="Répondre">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="changeReclamationStatus(${rec.id_reclamation})" title="Changer statut">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="exportReclamationPDF(${rec.id_reclamation})" title="PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function getPriorityBadgeClass(priority) {
    const classes = {
        'low': 'bg-success',
        'normal': 'bg-warning',
        'high': 'bg-danger'
    };
    return classes[priority] || 'bg-secondary';
}

function getStatusBadgeClass(status) {
    const classes = {
        'en attente': 'bg-info',
        'en cours': 'bg-warning',
        'résolue': 'bg-success'
    };
    return classes[status] || 'bg-secondary';
}

function getPriorityLabel(priority) {
    const labels = {
        'low': 'Basse',
        'normal': 'Normale',
        'high': 'Haute'
    };
    return labels[priority] || priority;
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function searchReclamations() {
    currentReclamationPage = 1;
    loadAllReclamations();
}

function updateReclamationPagination() {
    const start = (currentReclamationPage - 1) * reclamationLimit + 1;
    const end = Math.min(currentReclamationPage * reclamationLimit, totalReclamations);

    const info = document.getElementById('reclamationPaginationInfo');
    if (info) {
        info.textContent = `Affichage ${start}-${end} sur ${totalReclamations} réclamations`;
    }

    const prevBtn = document.getElementById('prevReclamationPage');
    const nextBtn = document.getElementById('nextReclamationPage');

    if (prevBtn) prevBtn.disabled = currentReclamationPage === 1;
    if (nextBtn) nextBtn.disabled = currentReclamationPage * reclamationLimit >= totalReclamations;
}

function previousReclamationPage() {
    if (currentReclamationPage > 1) {
        currentReclamationPage--;
        loadAllReclamations();
    }
}

function nextReclamationPage() {
    if (currentReclamationPage * reclamationLimit < totalReclamations) {
        currentReclamationPage++;
        loadAllReclamations();
    }
}

// Sorting functionality
function sortReclamations(column) {
    // Toggle sort direction if same column, otherwise default to ascending
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }

    // Sort the data
    allReclamationsData.sort((a, b) => {
        let valA, valB;

        switch (column) {
            case 'id':
                valA = a.id_reclamation;
                valB = b.id_reclamation;
                break;
            case 'sujet':
                valA = a.sujet.toLowerCase();
                valB = b.sujet.toLowerCase();
                break;
            case 'user':
                valA = a.user_name.toLowerCase();
                valB = b.user_name.toLowerCase();
                break;
            case 'priority':
                const priorityOrder = { 'low': 1, 'normal': 2, 'high': 3 };
                valA = priorityOrder[a.priority];
                valB = priorityOrder[b.priority];
                break;
            case 'status':
                const statusOrder = { 'en attente': 1, 'en cours': 2, 'résolue': 3 };
                valA = statusOrder[a.statut];
                valB = statusOrder[b.statut];
                break;
            case 'date':
                valA = new Date(a.date_creation);
                valB = new Date(b.date_creation);
                break;
            default:
                return 0;
        }

        // Compare values
        if (valA < valB) return currentSortDirection === 'asc' ? -1 : 1;
        if (valA > valB) return currentSortDirection === 'asc' ? 1 : -1;
        return 0;
    });

    // Update sort indicators
    updateSortIndicators();

    // Re-display sorted data
    displayReclamations(allReclamationsData);
}

function updateSortIndicators() {
    // Remove all existing sort indicators
    document.querySelectorAll('#reclamations th i').forEach(icon => {
        icon.className = 'fas fa-sort';
    });

    // Add indicator to current sort column
    const columnMap = {
        'id': 0,
        'sujet': 1,
        'user': 2,
        'priority': 3,
        'status': 4,
        'date': 5
    };

    const columnIndex = columnMap[currentSortColumn];
    if (columnIndex !== undefined) {
        const headers = document.querySelectorAll('#reclamations th');
        if (headers[columnIndex]) {
            const icon = headers[columnIndex].querySelector('i');
            if (icon) {
                icon.className = currentSortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            }
        }
    }
}

async function viewReclamationAdmin(id) {
    try {
        const response = await fetch(`reclamations_api.php?action=get_reclamation&id=${id}`);
        const data = await response.json();

        if (data.success) {
            const rec = data.reclamation;
            const modalHtml = `
                <div class="modal fade" id="viewReclamationModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Réclamation #${rec.id_reclamation}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <h6>Sujet:</h6>
                                    <p>${rec.sujet}</p>
                                </div>
                                <div class="mb-3">
                                    <h6>Description:</h6>
                                    <p>${rec.description}</p>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Utilisateur:</h6>
                                        <p>${rec.user_name} (${rec.user_email})</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Priorité:</h6>
                                        <p><span class="badge ${getPriorityBadgeClass(rec.priority)}">${getPriorityLabel(rec.priority)}</span></p>
                                    </div>
                                </div>
                                ${rec.full_name ? `<p><strong>Nom complet:</strong> ${rec.full_name}</p>` : ''}
                                ${rec.mobile_phone ? `<p><strong>Téléphone:</strong> ${rec.mobile_phone}</p>` : ''}
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Statut:</h6>
                                        <p><span class="badge ${getStatusBadgeClass(rec.statut)}">${rec.statut}</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Date de création:</h6>
                                        <p>${formatDateTime(rec.date_creation)}</p>
                                    </div>
                                </div>
                                ${rec.response_text ? `
                                    <div class="alert alert-info">
                                        <h6>Réponse admin:</h6>
                                        <p>${rec.response_text}</p>
                                        <small>Par ${rec.responder_name || 'Admin'} le ${formatDateTime(rec.response_date)}</small>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('viewReclamationModal');
            if (existingModal) existingModal.remove();

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('viewReclamationModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function respondToReclamation(id) {
    // Create a professional response modal
    const modalHtml = `
        <div class="modal fade" id="responseModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" style="border: 3px solid #28a745; border-radius: 15px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px;">
                        <h4 class="modal-title"><i class="fas fa-reply"></i> Répondre à la Réclamation #${id}</h4>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 30px; background: #f8f9fa;">
                        <div class="mb-3">
                            <p class="text-muted mb-2">
                                <i class="fas fa-info-circle"></i> Entrez votre réponse professionnelle pour cette réclamation
                            </p>
                        </div>
                        <div class="mb-3">
                            <label for="responseText" class="form-label" style="font-weight: 600; font-size: 16px;">
                                Votre Réponse <span class="text-danger">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="responseText" 
                                rows="6" 
                                placeholder="Écrivez votre réponse détaillée ici..."
                                style="border: 2px solid #dee2e6; border-radius: 8px; font-size: 15px;"
                                maxlength="1000"
                                required></textarea>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb"></i> Conseil: Soyez clair et professionnel
                                </small>
                                <small class="text-muted">
                                    <span id="charCount">0</span>/1000 caractères
                                </small>
                            </div>
                        </div>
                        <div class="alert alert-info" style="border-left: 4px solid #17a2b8;">
                            <i class="fas fa-bell"></i> <strong>Note:</strong> L'utilisateur sera notifié de votre réponse
                        </div>
                    </div>
                    <div class="modal-footer" style="background: white; padding: 20px; border-top: 2px solid #dee2e6;">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal" style="padding: 12px 30px; border-radius: 8px;">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="submitResponse(${id})" style="padding: 12px 30px; border-radius: 8px; font-weight: 600;">
                            <i class="fas fa-paper-plane"></i> Envoyer la Réponse
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('responseModal');
    if (existingModal) existingModal.remove();

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Add character counter functionality
    const textarea = document.getElementById('responseText');
    const charCount = document.getElementById('charCount');

    textarea.addEventListener('input', function () {
        charCount.textContent = this.value.length;
        if (this.value.length > 900) {
            charCount.style.color = '#dc3545';
        } else {
            charCount.style.color = '#6c757d';
        }
    });

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('responseModal'));
    modal.show();

    // Focus on textarea when modal is shown
    document.getElementById('responseModal').addEventListener('shown.bs.modal', function () {
        document.getElementById('responseText').focus();
    });
}

// Submit response function
async function submitResponse(id) {
    const responseText = document.getElementById('responseText').value.trim();

    if (!responseText) {
        alert('⚠️ Veuillez entrer une réponse avant d\'envoyer');
        document.getElementById('responseText').focus();
        return;
    }

    if (responseText.length < 10) {
        alert('⚠️ Votre réponse doit contenir au moins 10 caractères');
        document.getElementById('responseText').focus();
        return;
    }

    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user || user.role !== 'admin') {
            alert('❌ Vous devez être administrateur');
            return;
        }

        // Show loading state
        const submitBtn = event.target;
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

        const formData = new FormData();
        formData.append('action', 'respond_reclamation');
        formData.append('id', id);
        formData.append('response_text', responseText);
        formData.append('admin_id', user.id_user || user.id);

        const resp = await fetch('reclamations_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('responseModal'));
            if (modal) modal.hide();

            // Show success message
            showSuccessToast('✅ Réponse envoyée avec succès!');

            // Reload reclamations
            loadAllReclamations();
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            alert('❌ Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Erreur lors de l\'envoi de la réponse');
    }
}

// Success toast notification
function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed top-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast show" role="alert" style="background: #28a745; color: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
            <div class="toast-body" style="padding: 15px; font-size: 16px; font-weight: 600;">
                ${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

async function changeReclamationStatus(id) {
    // Create a modal for status selection
    const modalHtml = `
        <div class="modal fade" id="statusModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="border: 5px solid #000; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
                    <div class="modal-header" style="background: #000; color: #fff; padding: 25px; border-radius: 15px 15px 0 0;">
                        <h3 class="modal-title" style="font-size: 24px; font-weight: 800;">
                            <i class="fas fa-edit"></i> CHANGER LE STATUT
                        </h3>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="font-size: 24px;"></button>
                    </div>
                    <div class="modal-body" style="padding: 40px; background: #fff;">
                        <p class="text-center mb-4" style="font-size: 20px; color: #000; font-weight: 700;">
                            Choisissez le nouveau statut:
                        </p>
                        <div class="d-grid gap-4">
                            <button class="btn btn-lg" onclick="updateStatus(${id}, 'en attente')" 
                                style="background: #0dcaf0; color: #000; padding: 30px; font-size: 22px; font-weight: 800; border: 4px solid #000; box-shadow: 0 6px 12px rgba(0,0,0,0.4); border-radius: 15px; text-transform: uppercase;">
                                <i class="fas fa-clock" style="font-size: 32px; margin-right: 15px;"></i> EN ATTENTE
                            </button>
                            <button class="btn btn-lg" onclick="updateStatus(${id}, 'en cours')" 
                                style="background: #ffc107; color: #000; padding: 30px; font-size: 22px; font-weight: 800; border: 4px solid #000; box-shadow: 0 6px 12px rgba(0,0,0,0.4); border-radius: 15px; text-transform: uppercase;">
                                <i class="fas fa-hourglass-half" style="font-size: 32px; margin-right: 15px;"></i> EN COURS
                            </button>
                            <button class="btn btn-lg" onclick="updateStatus(${id}, 'résolue')" 
                                style="background: #198754; color: #fff; padding: 30px; font-size: 22px; font-weight: 800; border: 4px solid #000; box-shadow: 0 6px 12px rgba(0,0,0,0.4); border-radius: 15px; text-transform: uppercase;">
                                <i class="fas fa-check-circle" style="font-size: 32px; margin-right: 15px;"></i> RÉSOLUE
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #f8f9fa; padding: 20px; border-top: 3px solid #000; border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-lg" data-bs-dismiss="modal" 
                            style="background: #dc3545; color: #fff; padding: 15px 40px; font-size: 18px; font-weight: 700; border: 3px solid #000; border-radius: 10px;">
                            <i class="fas fa-times"></i> ANNULER
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('statusModal');
    if (existingModal) existingModal.remove();

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

// Separate function to actually update the status
async function updateStatus(id, status) {
    try {
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
        if (modal) modal.hide();

        const formData = new FormData();
        formData.append('action', 'change_status');
        formData.append('id', id);
        formData.append('status', status);

        const response = await fetch('reclamations_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Statut mis à jour avec succès');
            loadAllReclamations();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de la mise à jour du statut');
    }
}

function exportReclamationPDF(id) {
    window.open(`reclamations_api.php?action=export_pdf&id=${id}`, '_blank');
}

function exportAllReclamationsPDF() {
    window.open('reclamations_api.php?action=export_pdf&all=true', '_blank');
}

async function showReclamationStats() {
    try {
        const response = await fetch('reclamations_api.php?action=get_stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            const modalHtml = `
                <div class="modal fade" id="statsModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title">Statistiques des Réclamations</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <h6>Total: ${stats.total} réclamations</h6>
                                <hr>
                                <div class="mb-3">
                                    <strong>Priorité Basse:</strong> ${stats.counts.low} (${stats.percentages.low}%)
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: ${stats.percentages.low}%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong>Priorité Normale:</strong> ${stats.counts.normal} (${stats.percentages.normal}%)
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: ${stats.percentages.normal}%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong>Priorité Haute:</strong> ${stats.counts.high} (${stats.percentages.high}%)
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: ${stats.percentages.high}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const existingModal = document.getElementById('statsModal');
            if (existingModal) existingModal.remove();

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('statsModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors du chargement des statistiques');
    }
}

// Auto-load when tab is activated
document.addEventListener('DOMContentLoaded', function () {
    const reclamationsTab = document.getElementById('reclamations-tab');
    if (reclamationsTab) {
        reclamationsTab.addEventListener('shown.bs.tab', function () {
            loadAllReclamations();
        });
    }
});
