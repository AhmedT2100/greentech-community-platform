# Documentation Technique : Chatbot IA & Gestion des Profits

Ce document détaille l'implémentation technique et fonctionnelle des deux fonctionnalités majeures ajoutées à la plateforme GreenTechHub.

---

## 1. Chatbot IA (Intelligent Assistant) 🤖

Le chatbot a été intégré sur la page des événements (`evenements.html`) pour assister les utilisateurs en temps réel.

### A. Architecture Technique
*   **Moteur IA** : Utilise un LLM local via **LM Studio** (serveur local sur le port `1234`).
*   **Communication** : Appels API Fetch asynchrones vers `http://localhost:1234/v1/chat/completions`.
*   **Langage** : JavaScript pur (Vanilla JS) intégré dans `evenements.html`.

### B. Fonctionnement "Temps Réel" (RAG Simplifié)
Le chatbot ne se contente pas de réponses génériques. Il est "conscient" des événements actuels grâce à un système d'injection de contexte devéloppé sur mesure :

1.  **Récupération des Données** : Au chargement de la page, le script appelle l'API interne `events_api.php?action=get_events`.
2.  **Filtrage de Sécurité** : Avant d'envoyer les données à l'IA, une fonction `getEventsContext()` **filtre strictement** les informations :
    *   ✅ **Inclus** : Titre, Catégorie, Date, Lieu (Tunisie), Prix (DT), Places restantes.
    *   ❌ **Exclu** : Profit, Revenus, Données administratives.
3.  **Injection** : Ce contexte nettoyé est inséré dynamiquement dans le "System Prompt" avant chaque message de l'utilisateur.

### C. Configuration du "Cerveau" (System Prompt)
Le comportement du chatbot est régi par des règles strictes définies dans le code :
*   **Rôle** : Assistant GreenTechHub.
*   **Localisation** : Force la connaissance que tous les événements sont en **Tunisie**.
*   **Contraintes** : Réponses courtes (max ~30 mots), ton utile.
*   **Sécurité** : Instruction explicite de ne **JAMAIS** divulguer d'informations confidentielles ou financières.
*   **Hors-Sujet** : Refuse de répondre aux questions sur la politique, la météo, etc.

---

## 2. Gestion des Profits (Admin Panel) 💰

Cette fonctionnalité permet de suivre la rentabilité des événements de manière automatique et sécurisée.

### A. Base de Données
*   Une colonne `profit` (DECIMAL 10,2) a été ajoutée à la table `evenements`.
*   Le profit est stocké de manière persistante, ce qui évite de le recalculer à chaque affichage (gain de performance).

### B. Logique de Calcul
Le calcul est automatique et suit cette formule :
$$ \text{Profit} = \text{Nombre de Réservations Confirmées} \times \text{Prix de l'Événement} $$

*   *Note : Les événements gratuits (Prix = 0) ont toujours un profit de 0.*
*   *Note : Les réservations "En attente" ou "Refusées" ne comptent pas.*

### C. Workflow de Mise à Jour
1.  Un utilisateur réserve des places (Statut: `en_attente`). -> **Profit inchangé**.
2.  L'administrateur va dans le panneau d'administration (`admin.html`).
3.  L'administrateur clique sur **"Confirmer"**.
4.  Le Backend (`events_api.php`) :
    *   Passe la réservation à `confirmee`.
    *   Recalcule instantanément le profit total de l'événement.
    *   Met à jour la colonne `profit` dans la base de données.
5.  L'interface Admin se rafraîchit et affiche le nouveau montant en **Vert**.

### D. Sécurité et Confidentialité
*   **Front Office (Clients)** : Le profit est **totalement invisible**. Le code HTML et JavaScript du côté client a été nettoyé de toute référence au profit pour éviter les fuites de données.
*   **Back Office (Admin)** : Seuls les administrateurs connectés peuvent voir la colonne "Profit" dans le tableau de bord.
*   **Chatbot** : Comme mentionné plus haut, le chatbot a une interdiction stricte (via filtre de code + instructions IA) de lire ou mentionner cette donnée.

---

## Résumé des Fichiers Clés

| Fichier | Rôle |
| :--- | :--- |
| `evenements.html` | Interface Chatbot + Logique d'envoi et de filtrage des données événements. |
| `events_api.php` | API Backend qui fournit les événements et gère les réservations (calcul du profit). |
| `admin.js` | Gestion de l'affichage Admin (Affiche le profit, cache les badges de statut inutiles). |
| `mvc/Model/Event.php` | Modèle de données (Requêtes SQL pour lire/écrire le profit et ignorer le statut événement). |

---
*Document généré par l'Assistant IA - GreenTechHub*
