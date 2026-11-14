# Event Management Integration Summary

## Overview
The event management and reservation system has been successfully integrated into the GreenTechHub website.

## Changes Created

1. **Database Setup**
   - Tables `evenements` and `reservations` have been created (via `setup_events_db.php`).

2. **Backend API (`events_api.php`)**
   - Consolidates all logic for events and reservations.
   - Supports:
     - Listing/Getting events
     - Adding/Updating/Deleting events (Admin)
     - Making reservations (Public)
     - Confirming/Refusing reservations (Admin)
     - Calculating event profits (Admin)

3. **Frontend (`events.html`)**
   - New page linked from the main navigation.
   - Displays events in a card layout.
   - Includes real-time filtering by category.
   - Features a Reservation Modal.
   - Includes an AI Chatbot for user assistance.

4. **Admin Panel (`admin.html` & `admin.js`)**
   - Enhanced with two new tabs: "Événements" and "Réservations".
   - **Événements Tab**: List all events, view profits, add/edit/delete events.
   - **Réservations Tab**: View all reservations, confirm or refuse them.
   - Integrated with existing User management and Authentication system.

5. **Navigation**
   - Added "Events" link to the main navigation menu in `index.html`.

## How to Test
1. **Public Site**:  
   - Go to `index.html` -> Click "Events".
   - Browse events, use filters.
   - Try the Chatbot.
   - Click "Réserver" on an event.

2. **Admin Panel**:
   - Go to `admin.html` (ensure you are logged in as Admin).
   - Check the new "Événements" and "Réservations" tabs.
   - Try adding a new event.
   - Try confirming a reservation (check if available seats decrease).

## Notes
- The original `event-reservations` folder has been preserved as a backup but is no longer needed for functionality.
- Ensure `config.php` connects to the correct database where tables were created.
