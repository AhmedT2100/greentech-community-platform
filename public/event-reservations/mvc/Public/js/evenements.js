// evenements.js - Gestion des événements côté front office with PDO

document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    loadMyReservations();

    // refresh button removed — no manual refresh needed

    const cancelReservationBtn = document.getElementById('cancelReservationBtn');
    if (cancelReservationBtn) cancelReservationBtn.addEventListener('click', closeModal);

    const confirmReservationBtn = document.getElementById('confirmReservationBtn');
    if (confirmReservationBtn) confirmReservationBtn.addEventListener('click', confirmReservation);
});

let selectedEvent = null;

// READ - Charger les événements
async function loadEvents() {
    try {
        const response = await fetch('events_api.php?action=get_events');
        const data = await response.json();
        
        const container = document.getElementById('eventsList');
        container.innerHTML = '';

        if (data.success && data.events.length > 0) {
            data.events.forEach(event => {
                const card = document.createElement('div');
                card.className = 'event-card';
                
                const heure = event.heure_debut ? 
                    `${event.heure_debut.substring(0, 5)}${event.heure_fin ? ` - ${event.heure_fin.substring(0, 5)}` : ''}` : 
                    '';
                
                card.innerHTML = `
                    <div class="event-header">
                        <h3 class="event-title">${event.titre}</h3>
                        <span class="event-category">${event.categorie}</span>
                    </div>
                    <p class="event-description">${event.description}</p>
                    <div class="event-details">
                        <div class="event-detail"><i class="fas fa-calendar"></i> ${event.date_event}</div>
                        ${heure ? `<div class="event-detail"><i class="fas fa-clock"></i> ${heure}</div>` : ''}
                        <div class="event-detail"><i class="fas fa-map-marker-alt"></i> ${event.lieu}</div>
                    </div>
                    <div class="event-actions">
                        <div class="event-price ${event.prix == 0 ? 'free' : ''}">
                            ${event.prix == 0 ? 'GRATUIT' : event.prix + ' DT'}
                        </div>
                        <div class="event-places">
                            <i class="fas fa-users"></i> ${event.places_restantes} places restantes
                        </div>
                                <button class="btn-reserve" ${event.places_restantes === 0 ? 'disabled' : ''}
                                    data-event-id="${event.id_event}"
                                    data-event-title="${event.titre.replace(/\"/g, '&quot;').replace(/'/g, "&#39;") }"
                                    data-event-places="${event.places_restantes}">
                                    ${event.places_restantes === 0 ? 'Complet' : 'Réserver'}
                                </button>
                    </div>
                `;
                container.appendChild(card);

                // attach click listener to the reserve button safely
                const btn = card.querySelector('.btn-reserve');
                if (btn) {
                    btn.addEventListener('click', (e) => {
                        const id = parseInt(btn.getAttribute('data-event-id')) || null;
                        const title = btn.getAttribute('data-event-title') || '';
                        const places = parseInt(btn.getAttribute('data-event-places')) || 0;
                        if (id) openReservationModal(id, title, places);
                    });
                }
            });
        } else {
            container.innerHTML = '<p class="text-muted text-center">Aucun événement disponible</p>';
        }
    } catch (error) {
        console.error('Erreur:', error);
        document.getElementById('eventsList').innerHTML = '<p class="text-danger text-center">Erreur de chargement des événements</p>';
    }
}

// Ouvrir modal de réservation
function openReservationModal(eventId, eventTitle, placesRestantes) {
    selectedEvent = { 
        id: eventId, 
        titre: eventTitle, 
        places: placesRestantes 
    };
    const titleEl = document.getElementById('modalEventTitle');
    const placesInput = document.getElementById('placesInput');
    const modal = document.getElementById('reservationModal');

    if (!modal) {
        console.warn('openReservationModal: reservation modal not found');
        return;
    }

    if (titleEl) titleEl.textContent = eventTitle || '';
    if (placesInput) {
        placesInput.max = Math.min(placesRestantes || 0, 10);
        placesInput.value = 1;
    }

    // show modal (simple display toggle; can be converted to Bootstrap modal API)
    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('reservationModal');
    if (modal) modal.style.display = 'none';
    selectedEvent = null;
}

// CREATE - Confirmer la réservation
async function confirmReservation() {
    if (!selectedEvent) return;
    
    const placesEl = document.getElementById('placesInput');
    const places = placesEl ? parseInt(placesEl.value) : 1;
    const nom_utilisateur = (document.getElementById('reservationName') || {}).value || '';
    const email_utilisateur = (document.getElementById('reservationEmail') || {}).value || '';

    if (!nom_utilisateur || !email_utilisateur) {
        alert('Veuillez remplir votre nom et email');
        return;
    }

    if (places < 1 || places > selectedEvent.places) {
        alert('Nombre de places invalide');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id_event', selectedEvent.id);
        formData.append('quantite', places);
        formData.append('nom_utilisateur', nom_utilisateur);
        formData.append('email_utilisateur', email_utilisateur);
        
        const response = await fetch('events_api.php?action=reserve_event', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`✅ Réservation de ${places} place(s) pour "${selectedEvent.titre}" effectuée avec succès !`);
            closeModal();
            loadEvents();
            loadMyReservations();
        } else {
            alert('❌ ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur lors de la réservation');
    }
}

// READ - Charger mes réservations
async function loadMyReservations() {
    try {
        const response = await fetch('events_api.php?action=get_all_reservations');
        const data = await response.json();
        
        const container = document.getElementById('myReservations');
        if (!container) return;
        container.innerHTML = '';
        
        if (data.success && data.reservations.length > 0) {
            data.reservations.forEach(reservation => {
                const div = document.createElement('div');
                div.className = 'reservation-item';
                div.innerHTML = `
                    <div class="reservation-info">
                        <h4>${reservation.titre}</h4>
                        <div class="reservation-details">
                            <span>${reservation.quantite} place(s)</span>
                            <span>${reservation.date_event}</span>
                            <span>${reservation.lieu}</span>
                        </div>
                    </div>
                    <div class="reservation-actions">
                        <div class="reservation-status status-${reservation.statut}\">${reservation.statut.replace('_', ' ').toUpperCase()}</div>
                    </div>
                `;
                container.appendChild(div);
            });
        } else {
            container.innerHTML = '<p class="text-muted text-center">Aucune réservation</p>';
        }
    } catch (error) {
        console.error('Erreur:', error);
        const container = document.getElementById('myReservations');
        if (container) container.innerHTML = '<p class="text-danger text-center">Erreur de chargement des réservations</p>';
    }
}
