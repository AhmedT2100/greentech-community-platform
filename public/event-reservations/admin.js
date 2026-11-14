// admin.js - Working version
document.addEventListener('DOMContentLoaded', function () {
    console.log("Admin loaded");
    loadEvents();
    loadReservations();
});

// Keep track of the element that had focus before opening a modal
let _lastFocusedBeforeModal = null;

// Ensure modal is interactive: remove stray backdrops, clear aria-hidden/inert, enable form controls
function prepareModalForShow(modalEl) {
    // remove any leftover backdrops to avoid them covering the modal
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

    // ensure modal is not inert or aria-hidden
    try {
        modalEl.removeAttribute('inert');
        modalEl.removeAttribute('aria-hidden');
    } catch (e) { }

    // enable any disabled elements inside the modal (defensive)
    modalEl.querySelectorAll('input[disabled], textarea[disabled], select[disabled], button[disabled]').forEach(el => {
        el.removeAttribute('disabled');
    });

    // make sure modal has proper display style
    modalEl.style.zIndex = 2000;
}

// Load events
async function loadEvents() {
    try {
        console.log("Loading events...");
        const response = await fetch('mvc/Controller/event_controller.php?action=api_list');
        const data = await response.json();
        console.log("Events data:", data);

        if (data.success) {
            console.log('Events loaded:', data.events); // DEBUG
            console.log('First event profit:', data.events[0]?.profit); // DEBUG
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
    tbody.innerHTML = '';

    if (events && events.length > 0) {
        events.forEach(event => {
            console.log('Processing event:', event.titre, 'Profit raw:', event.profit, 'Type:', typeof event.profit); // DEBUG
            const profit = parseFloat(event.profit || 0).toFixed(2);
            console.log('Profit formatted:', profit); // DEBUG
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${event.id_event}</td>
                <td><strong>${event.titre}</strong></td>
                <td>${event.date_event}</td>
                <td>${event.lieu}</td>
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
        console.log("Reservations data:", data);

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
    tbody.innerHTML = '';

    if (reservations && reservations.length > 0) {
        reservations.forEach(res => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${res.id_reserv}</td>
                <td>${res.nom_utilisateur}</td>
                <td>${res.email_utilisateur}</td>
                <td>${res.titre}</td>
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
                    ` : '<span class="text-muted">Traité</span>'}
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

    // Show modal using Bootstrap

    const modalEl = document.getElementById('eventModal');
    // store currently focused element so we can restore focus when modal closes
    _lastFocusedBeforeModal = document.activeElement;

    // prepare modal (remove stray backdrops, ensure enabled)
    prepareModalForShow(modalEl);

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // When the modal is hidden, restore focus to the previously focused element
    modalEl.addEventListener('hidden.bs.modal', function handler() {
        // remove handler (only want once)
        modalEl.removeEventListener('hidden.bs.modal', handler);
        try {
            if (_lastFocusedBeforeModal && typeof _lastFocusedBeforeModal.focus === 'function') {
                _lastFocusedBeforeModal.focus();
            } else {
                // fallback: move focus to body
                document.body.focus();
            }
        } catch (e) {
            // ignore focus restore errors
        }
        _lastFocusedBeforeModal = null;
    });
    // remove any stray backdrops after hiding
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
}

// Save event
async function saveEvent() {
    const id_event = document.getElementById('editEventId').value;
    const isEdit = !!id_event;

    // Get form values
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
        formData.append('id_event', id_event);
    }

    try {
        const endpoint = isEdit ? 'mvc/Controller/event_controller.php?action=api_update' : 'mvc/Controller/event_controller.php?action=api_create';
        // For update API we expect field 'id'
        if (isEdit) {
            // admin form earlier used 'id_event' in other places; normalize to 'id'
            formData.append('id', id_event);
            // include places_restantes if available
            const placesRestantesEl = document.getElementById('eventPlacesRestantes');
            if (placesRestantesEl) formData.append('places_restantes', placesRestantesEl.value);
        }

        const response = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await response.json();
        alert(data.message || JSON.stringify(data));

        if (data.success) {
            const modalEl = document.getElementById('eventModal');
            try { const active = document.activeElement; if (modalEl.contains(active) && typeof active.blur === 'function') active.blur(); } catch (e) { }
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            loadEvents();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur: ' + error.message);
    }
}

// Edit event (placeholder - will implement later)
function editEvent(id) {
    // fetch event details and populate modal
    fetch(`mvc/Controller/event_controller.php?action=api_get&id=${encodeURIComponent(id)}`)
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
            // store places_restantes in a hidden input if needed
            let restEl = document.getElementById('eventPlacesRestantes');
            if (!restEl) {
                restEl = document.createElement('input');
                restEl.type = 'hidden'; restEl.id = 'eventPlacesRestantes'; restEl.name = 'places_restantes';
                document.getElementById('eventForm').appendChild(restEl);
            }
            restEl.value = event.places_restantes || 0;

            const modalEl = document.getElementById('eventModal');
            _lastFocusedBeforeModal = document.activeElement;
            // prepare modal before showing
            prepareModalForShow(modalEl);
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            modalEl.addEventListener('hidden.bs.modal', function handler() {
                modalEl.removeEventListener('hidden.bs.modal', handler);
                try { if (_lastFocusedBeforeModal && typeof _lastFocusedBeforeModal.focus === 'function') _lastFocusedBeforeModal.focus(); } catch (e) { }
                _lastFocusedBeforeModal = null;
            });
        })
        .catch(err => { console.error(err); alert('Erreur de récupération de l\'événement'); });
}

// Delete event (placeholder - will implement later)
function deleteEvent(id) {
    if (!confirm('Voulez-vous vraiment supprimer cet événement?')) return;
    const formData = new FormData();
    formData.append('id', id);
    fetch('mvc/Controller/event_controller.php?action=api_delete', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.message || JSON.stringify(data));
            if (data.success) loadEvents();
        })
        .catch(err => { console.error(err); alert('Erreur suppression'); });
}

// Update reservation status (placeholder - will implement later)
async function updateReservationStatus(id, status) {
    if (!id || !status) return;
    try {
        // send POST to events_api.php to update reservation status
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
            // refresh lists
            loadReservations();
            loadEvents();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible de mettre à jour'));
            console.error('updateReservationStatus error:', data);
        }
    } catch (err) {
        console.error('Network error updating reservation:', err);
        alert('Erreur réseau: ' + (err.message || err));
    }
}