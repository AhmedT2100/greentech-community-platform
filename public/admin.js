// admin.js - Gestion de l'administration GreenTechHub

// Vérifier que l'utilisateur est admin avant de faire quoi que ce soit
if (typeof localStorage !== 'undefined') {
    const userData = localStorage.getItem('user');
    if (!userData) {
        window.location.href = 'index.html';
    } else {
        const user = JSON.parse(userData);
        if (user.role !== 'admin') {
            window.location.href = 'index.html';
        }
    }
}

// Variables globales pour la pagination
let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
const usersPerPage = 5;

document.addEventListener('DOMContentLoaded', function () {
    console.log("Panel d'administration GreenTechHub initialisé");
    loadAdminData();
});

// Gérer les erreurs d'authentification
function handleAuthError(data) {
    if (data.message === 'Non authentifié') {
        localStorage.removeItem('user');
        window.location.href = 'index.html';
        return true;
    }
    return false;
}

// Charger toutes les données d'administration
async function loadAdminData() {
    await loadUsers();
    await loadHistory();
    await loadStats();
    await loadEvents();
    await loadReservations();
}

// Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('admin_api.php?action=stats');
        const data = await response.json();

        if (data.success) {
            document.getElementById('totalUsers').textContent = data.stats.totalUsers;
            document.getElementById('totalInnovations').textContent = data.stats.totalInnovations;
            document.getElementById('totalAdmins').textContent = data.stats.totalAdmins;
        } else {
            if (handleAuthError(data)) return;
            console.error('Erreur stats:', data.message);
            showError('Erreur lors du chargement des statistiques: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        showError('Erreur de connexion au serveur');
    }
}

// Charger la liste des utilisateurs
async function loadUsers() {
    try {
        const usersTable = document.getElementById('usersTable');
        usersTable.innerHTML = '<tr><td colspan="6" class="text-center">Chargement...</td></tr>';

        const response = await fetch('admin_api.php?action=users');
        const data = await response.json();

        if (data.success) {
            allUsers = data.users;
            filteredUsers = allUsers;
            currentPage = 1;
            displayUsersPage();
            updatePaginationControls();
        } else {
            if (handleAuthError(data)) return;
            usersTable.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Erreur: ${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error);
        document.getElementById('usersTable').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erreur de connexion au serveur</td></tr>';
    }
}

// Rechercher des utilisateurs
function searchUsers() {
    const searchTerm = document.getElementById('searchUser').value.toLowerCase().trim();

    if (searchTerm === '') {
        filteredUsers = allUsers;
    } else {
        filteredUsers = allUsers.filter(user => {
            const nom = user.nom.toLowerCase();
            return nom.includes(searchTerm);
        });
    }

    currentPage = 1;
    displayUsersPage();
    updatePaginationControls();
}

// Afficher la page actuelle des utilisateurs
function displayUsersPage() {
    const usersTable = document.getElementById('usersTable');

    if (filteredUsers.length === 0) {
        usersTable.innerHTML = '<tr><td colspan="6" class="text-center">Aucun utilisateur trouvé</td></tr>';
        return;
    }

    const startIndex = (currentPage - 1) * usersPerPage;
    const endIndex = Math.min(startIndex + usersPerPage, filteredUsers.length);
    const usersToDisplay = filteredUsers.slice(startIndex, endIndex);

    usersTable.innerHTML = '';

    usersToDisplay.forEach((user, index) => {
        const rowNumber = startIndex + index + 1; // Numéro de ligne global
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${rowNumber}</strong></td>
            <td>${user.id_user}</td>
            <td>${escapeHtml(user.nom)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <span class="badge ${getRoleBadgeClass(user.role)}">${user.role}</span>
                ${user.is_banned == 1 ? '<span class="badge bg-dark ms-1">Banni</span>' : ''}
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id_user}, '${escapeHtml(user.nom)}', '${escapeHtml(user.email)}', '${user.role}')" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                ${user.is_banned == 1
                ? `<button class="btn btn-sm btn-outline-success me-1" onclick="toggleBanUser(${user.id_user}, false)" title="Débannir"><i class="fas fa-user-check"></i></button>`
                : `<button class="btn btn-sm btn-outline-dark me-1" onclick="toggleBanUser(${user.id_user}, true)" title="Bannir"><i class="fas fa-user-slash"></i></button>`
            }
                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id_user})" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        usersTable.appendChild(row);
    });
}

// Mettre à jour les contrôles de pagination
function updatePaginationControls() {
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    const startIndex = (currentPage - 1) * usersPerPage + 1;
    const endIndex = Math.min(currentPage * usersPerPage, filteredUsers.length);

    // Mettre à jour le texte d'information
    document.getElementById('paginationInfo').textContent =
        `Affichage ${startIndex}-${endIndex} sur ${filteredUsers.length} utilisateurs`;

    // Activer/désactiver les boutons
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
}

// Page précédente
function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        displayUsersPage();
        updatePaginationControls();
    }
}

// Page suivante
function nextPage() {
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        displayUsersPage();
        updatePaginationControls();
    }
}

// Charger l'historique des innovations
async function loadHistory() {
    try {
        const historyTable = document.getElementById('historyTable');
        historyTable.innerHTML = '<tr><td colspan="4" class="text-center">Chargement...</td></tr>';

        const response = await fetch('admin_api.php?action=history');
        const data = await response.json();

        if (data.success) {
            displayHistory(data.history);
        } else {
            if (handleAuthError(data)) return;
            historyTable.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Erreur: ${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'historique:', error);
        document.getElementById('historyTable').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erreur de connexion au serveur</td></tr>';
    }
}

// Afficher l'historique dans le tableau
function displayHistory(history) {
    const historyTable = document.getElementById('historyTable');

    if (history.length === 0) {
        historyTable.innerHTML = '<tr><td colspan="4" class="text-center">Aucun historique trouvé</td></tr>';
        return;
    }

    historyTable.innerHTML = '';

    history.forEach(record => {
        const row = document.createElement('tr');
        const date = new Date(record.date_post);
        const formattedDate = date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR');

        row.innerHTML = `
            <td>${record.id_historique}</td>
            <td>${escapeHtml(record.nom_utilisateur)} (ID: ${record.id_user})</td>
            <td>${record.id_innovation}</td>
            <td>${formattedDate}</td>
        `;
        historyTable.appendChild(row);
    });
}

// Obtenir la classe CSS pour le badge du rôle
function getRoleBadgeClass(role) {
    switch (role) {
        case 'admin': return 'bg-danger';
        case 'moderateur': return 'bg-warning';
        default: return 'bg-secondary';
    }
}

// Échapper les caractères HTML pour la sécurité
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Éditer un utilisateur
function editUser(id, nom, email, role) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUserName').value = nom;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value = role;

    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

// Mettre à jour un utilisateur
async function updateUser() {
    const id = document.getElementById('editUserId').value;
    const nom = document.getElementById('editUserName').value;
    const email = document.getElementById('editUserEmail').value;
    const role = document.getElementById('editUserRole').value;

    if (!nom || !email || !role) {
        alert('Veuillez remplir tous les champs');
        return;
    }

    try {
        const response = await fetch('admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_user&id=${id}&nom=${encodeURIComponent(nom)}&email=${encodeURIComponent(email)}&role=${role}`
        });

        const data = await response.json();

        if (data.success) {
            alert('Utilisateur mis à jour avec succès!');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            await loadUsers();
            await loadStats();
        } else {
            if (handleAuthError(data)) return;
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour:', error);
        alert('Erreur lors de la mise à jour de l\'utilisateur');
    }
}

// Supprimer un utilisateur
async function deleteUser(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        return;
    }

    try {
        const response = await fetch('admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&id=${id}`
        });

        const data = await response.json();

        if (data.success) {
            alert('Utilisateur supprimé avec succès!');
            await loadUsers();
            await loadStats();
        } else {
            if (handleAuthError(data)) return;
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression de l\'utilisateur');
    }
}

// Bannir ou débannir un utilisateur
async function toggleBanUser(id, shouldBan) {
    const action = shouldBan ? 'ban_user' : 'unban_user';
    const confirmMsg = shouldBan
        ? 'Êtes-vous sûr de vouloir bannir cet utilisateur ? Il ne pourra plus se connecter.'
        : 'Êtes-vous sûr de vouloir débannir cet utilisateur ?';

    if (!confirm(confirmMsg)) {
        return;
    }

    try {
        const response = await fetch('admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${action}&id=${id}`
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            await loadUsers();
        } else {
            if (handleAuthError(data)) return;
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur lors du changement de statut:', error);
        alert('Erreur lors de l\'opération');
    }
}

// Fonctions de rafraîchissement
function refreshUsers() {
    loadUsers();
}

function refreshHistory() {
    loadHistory();
}

// Afficher une erreur
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    errorDiv.style.zIndex = '9999';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(errorDiv);

    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Déconnexion
async function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        try {
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('auth.php', { method: 'POST', body: formData });
        } catch (e) {
            console.error('Erreur lors de la déconnexion:', e);
        }

        localStorage.removeItem('user');
        window.location.href = 'index.html';
    }
}

// Keep track of the element that had focus before opening a modal
let _lastFocusedBeforeModal = null;

// Ensure modal is interactive
function prepareModalForShow(modalEl) {
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    try {
        modalEl.removeAttribute('inert');
        modalEl.removeAttribute('aria-hidden');
    } catch (e) { }
    modalEl.querySelectorAll('input[disabled], textarea[disabled], select[disabled], button[disabled]').forEach(el => {
        el.removeAttribute('disabled');
    });
    modalEl.style.zIndex = 2000;
}

// Load events
async function loadEvents() {
    try {
        console.log("Loading events...");
        const response = await fetch('events_api.php?action=get_events');
        const data = await response.json();

        if (data.success) {
            displayEvents(data.events);
        } else {
            console.error('API Error:', data.message);
            document.getElementById('eventsTable').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur: ' + data.message + '</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('eventsTable').innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erreur de connexion: ' + error.message + '</td></tr>';
    }
}

// Display events in table
function displayEvents(events) {
    const tbody = document.getElementById('eventsTable');
    if (!tbody) return; // Guard clause if tab not present
    tbody.innerHTML = '';

    if (events && events.length > 0) {
        events.forEach(event => {
            const profit = parseFloat(event.profit || 0).toFixed(2);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${event.id_event}</td>
                <td><strong>${escapeHtml(event.titre)}</strong></td>
                <td>${event.date_event}</td>
                <td>${escapeHtml(event.lieu)}</td>
                <td>${event.places_restantes}/${event.places_total}</td>
                <td>${event.prix == 0 ? 'Gratuit' : event.prix + ' DT'}</td>
                <td><strong style="color: #1dbf73;">${profit} DT</strong></td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" onclick="editEvent(${event.id_event})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteEvent(${event.id_event})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Aucun événement trouvé</td></tr>';
    }
}

// Load reservations
async function loadReservations() {
    try {
        const response = await fetch('events_api.php?action=get_all_reservations');
        const data = await response.json();

        if (data.success) {
            displayReservations(data.reservations);
        } else {
            console.error('Reservations error:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Display reservations
function displayReservations(reservations) {
    const tbody = document.getElementById('reservationsTable');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (reservations && reservations.length > 0) {
        reservations.forEach(res => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${res.id_reserv}</td>
                <td>${escapeHtml(res.nom_utilisateur)}</td>
                <td>${escapeHtml(res.email_utilisateur)}</td>
                <td>${escapeHtml(res.titre)}</td>
                <td>${res.date_event}</td>
                <td>${res.quantite}</td>
                <td>
                    ${res.statut === 'en_attente' ? `
                        <button class="btn btn-sm btn-success me-1" onclick="updateReservationStatus(${res.id_reserv}, 'confirmee')">
                            <i class="fas fa-check"></i> Confirmer
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateReservationStatus(${res.id_reserv}, 'refusee')">
                            <i class="fas fa-times"></i> Refuser
                        </button>
                    ` : `<span class="badge ${res.statut === 'confirmee' ? 'bg-success' : 'bg-danger'}">${res.statut}</span>`}
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Aucune réservation</td></tr>';
    }
}

// Open add event modal
function openAddEventModal() {
    document.getElementById('eventModalTitle').textContent = 'Ajouter un Événement';
    document.getElementById('eventForm').reset();
    document.getElementById('editEventId').value = '';

    const modalEl = document.getElementById('eventModal');
    _lastFocusedBeforeModal = document.activeElement;
    prepareModalForShow(modalEl);
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', function handler() {
        modalEl.removeEventListener('hidden.bs.modal', handler);
        try { if (_lastFocusedBeforeModal) _lastFocusedBeforeModal.focus(); } catch (e) { }
        _lastFocusedBeforeModal = null;
    });
}

// Save event
async function saveEvent() {
    const id_event = document.getElementById('editEventId').value;
    const isEdit = !!id_event;

    const formData = new FormData();
    formData.append('titre', document.getElementById('eventTitre').value);
    formData.append('description', document.getElementById('eventDescription').value);
    formData.append('categorie', document.getElementById('eventCategorie').value);
    formData.append('date_event', document.getElementById('eventDate').value);
    formData.append('heure_debut', document.getElementById('eventHeureDebut').value);
    formData.append('heure_fin', document.getElementById('eventHeureFin').value);
    formData.append('lieu', document.getElementById('eventLieu').value);
    formData.append('prix', document.getElementById('eventPrix').value);
    formData.append('places_total', document.getElementById('eventPlaces').value);

    if (isEdit) {
        formData.append('id', id_event);
    }

    try {
        const endpoint = isEdit ? 'events_api.php?action=update_event' : 'events_api.php?action=add_event';
        
        const response = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await response.json();
        alert(data.message || JSON.stringify(data));

        if (data.success) {
            const modalEl = document.getElementById('eventModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            loadEvents();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur: ' + error.message);
    }
}

// Edit event
function editEvent(id) {
    fetch(`events_api.php?action=get_event&id=${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return alert('Erreur: ' + (data.message || 'Impossible de récupérer l\'événement'));
            const event = data.event;
            document.getElementById('eventModalTitle').textContent = 'Modifier un Événement';
            document.getElementById('eventForm').reset();
            document.getElementById('editEventId').value = event.id_event || id;
            document.getElementById('eventTitre').value = event.titre || '';
            document.getElementById('eventDescription').value = event.description || '';
            document.getElementById('eventCategorie').value = event.categorie || '';
            document.getElementById('eventDate').value = event.date_event || '';
            document.getElementById('eventHeureDebut').value = event.heure_debut || '';
            document.getElementById('eventHeureFin').value = event.heure_fin || '';
            document.getElementById('eventLieu').value = event.lieu || '';
            document.getElementById('eventPrix').value = event.prix || 0;
            document.getElementById('eventPlaces').value = event.places_total || 0;

            const modalEl = document.getElementById('eventModal');
            _lastFocusedBeforeModal = document.activeElement;
            prepareModalForShow(modalEl);
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            modalEl.addEventListener('hidden.bs.modal', function handler() {
                modalEl.removeEventListener('hidden.bs.modal', handler);
                try { if (_lastFocusedBeforeModal) _lastFocusedBeforeModal.focus(); } catch (e) { }
                _lastFocusedBeforeModal = null;
            });
        })
        .catch(err => { console.error(err); alert('Erreur de récupération de l\'événement'); });
}

// Delete event
function deleteEvent(id) {
    if (!confirm('Voulez-vous vraiment supprimer cet événement?')) return;
    const formData = new FormData();
    formData.append('id', id);
    fetch('events_api.php?action=delete_event', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message || JSON.stringify(data));
            if (data.success) loadEvents();
        })
        .catch(err => { console.error(err); alert('Erreur suppression'); });
}

// Update reservation status
async function updateReservationStatus(id, status) {
    if (!id || !status) return;
    try {
        const formData = new FormData();
        formData.append('id_reserv', id);
        formData.append('status', status);

        const resp = await fetch('events_api.php?action=update_reservation_status', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            alert(data.message || 'Statut mis à jour');
            loadReservations();
            loadEvents();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible de mettre à jour'));
        }
    } catch (err) {
        console.error('Network error updating reservation:', err);
        alert('Erreur réseau: ' + (err.message || err));
    }
}