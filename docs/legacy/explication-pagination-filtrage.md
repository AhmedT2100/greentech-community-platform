# Explication : Pagination et Filtrage des Utilisateurs

## Vue d'ensemble

Le système de gestion des utilisateurs dans `admin.html` affiche les utilisateurs **5 par page** avec une fonctionnalité de **recherche/filtrage** en temps réel. Voici comment cela fonctionne.

---

## 📊 Structure des Données

### Variables Globales (dans `admin.js`)

```javascript
let allUsers = [];        // Tous les utilisateurs chargés depuis la base de données
let filteredUsers = [];   // Utilisateurs après filtrage (recherche)
let currentPage = 1;      // Page actuelle (commence à 1)
const usersPerPage = 5;   // Nombre d'utilisateurs par page (constant)
```

---

## 🔄 Flux de Fonctionnement

### 1. Chargement Initial des Données

Quand la page `admin.html` se charge :

```javascript
// Dans admin.js, ligne 38-42
async function loadAdminData() {
    await loadUsers();      // Charge tous les utilisateurs
    await loadHistory();    // Charge l'historique
    await loadStats();      // Charge les statistiques
}
```

### 2. Fonction `loadUsers()` - Récupération des Données

```javascript
// Ligne 66-88
async function loadUsers() {
    try {
        // 1. Affiche "Chargement..." dans le tableau
        const usersTable = document.getElementById('usersTable');
        usersTable.innerHTML = '<tr><td colspan="6" class="text-center">Chargement...</td></tr>';

        // 2. Appel API pour récupérer tous les utilisateurs
        const response = await fetch('admin_api.php?action=users');
        const data = await response.json();

        if (data.success) {
            // 3. Stocke tous les utilisateurs
            allUsers = data.users;
            
            // 4. Au début, filteredUsers = allUsers (pas de filtre)
            filteredUsers = allUsers;
            
            // 5. Réinitialise à la page 1
            currentPage = 1;
            
            // 6. Affiche la première page
            displayUsersPage();
            
            // 7. Met à jour les boutons de pagination
            updatePaginationControls();
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}
```

---

## 📄 Pagination - Affichage par 5

### Fonction `displayUsersPage()` - Affichage d'une Page

```javascript
// Ligne 109-150
function displayUsersPage() {
    const usersTable = document.getElementById('usersTable');

    // 1. Vérifier s'il y a des utilisateurs à afficher
    if (filteredUsers.length === 0) {
        usersTable.innerHTML = '<tr><td colspan="6" class="text-center">Aucun utilisateur trouvé</td></tr>';
        return;
    }

    // 2. CALCUL DES INDICES
    // Si currentPage = 1 : startIndex = (1-1) * 5 = 0
    // Si currentPage = 2 : startIndex = (2-1) * 5 = 5
    // Si currentPage = 3 : startIndex = (3-1) * 5 = 10
    const startIndex = (currentPage - 1) * usersPerPage;
    
    // endIndex = minimum entre (startIndex + 5) et le nombre total d'utilisateurs
    // Exemple : Si on a 12 utilisateurs et currentPage = 3
    // startIndex = 10, endIndex = min(15, 12) = 12
    const endIndex = Math.min(startIndex + usersPerPage, filteredUsers.length);
    
    // 3. EXTRACTION DES UTILISATEURS À AFFICHER
    // slice(start, end) extrait les éléments de l'index start à end-1
    // Exemple : filteredUsers.slice(0, 5) → utilisateurs 0, 1, 2, 3, 4
    const usersToDisplay = filteredUsers.slice(startIndex, endIndex);

    // 4. AFFICHAGE DES UTILISATEURS
    usersTable.innerHTML = ''; // Vide le tableau

    usersToDisplay.forEach((user, index) => {
        // Calcul du numéro de ligne global
        // Page 1 : rowNumber = 0 + 0 + 1 = 1, 0 + 1 + 1 = 2, etc.
        // Page 2 : rowNumber = 5 + 0 + 1 = 6, 5 + 1 + 1 = 7, etc.
        const rowNumber = startIndex + index + 1;
        
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
                <!-- Boutons d'action -->
                <button class="btn btn-sm btn-outline-primary me-1" 
                        onclick="editUser(${user.id_user}, '${escapeHtml(user.nom)}', '${escapeHtml(user.email)}', '${user.role}')" 
                        title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                ${user.is_banned == 1
                    ? `<button class="btn btn-sm btn-outline-success me-1" onclick="toggleBanUser(${user.id_user}, false)" title="Débannir"><i class="fas fa-user-check"></i></button>`
                    : `<button class="btn btn-sm btn-outline-dark me-1" onclick="toggleBanUser(${user.id_user}, true)" title="Bannir"><i class="fas fa-user-slash"></i></button>`
                }
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteUser(${user.id_user})" 
                        title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        usersTable.appendChild(row);
    });
}
```

### Exemple Concret de Pagination

**Scénario : 12 utilisateurs au total, usersPerPage = 5**

| Page | startIndex | endIndex | Utilisateurs affichés |
|------|------------|----------|-----------------------|
| 1    | 0          | 5        | 0, 1, 2, 3, 4         |
| 2    | 5          | 10       | 5, 6, 7, 8, 9         |
| 3    | 10         | 12       | 10, 11                |

---

## 🔍 Filtrage - Recherche en Temps Réel

### Fonction `searchUsers()` - Filtrage des Utilisateurs

```javascript
// Ligne 91-106
function searchUsers() {
    // 1. Récupère le texte de recherche (en minuscules et sans espaces)
    const searchTerm = document.getElementById('searchUser').value.toLowerCase().trim();

    // 2. Si la recherche est vide, affiche tous les utilisateurs
    if (searchTerm === '') {
        filteredUsers = allUsers;
    } else {
        // 3. Filtre les utilisateurs dont le nom contient le terme de recherche
        filteredUsers = allUsers.filter(user => {
            const nom = user.nom.toLowerCase();
            return nom.includes(searchTerm);
        });
    }

    // 4. Retour à la page 1 après filtrage
    currentPage = 1;
    
    // 5. Affiche les résultats filtrés
    displayUsersPage();
    
    // 6. Met à jour la pagination
    updatePaginationControls();
}
```

### Comment le Filtrage est Déclenché

Dans `admin.html`, l'input de recherche a un événement `onkeyup` :

```html
<input type="text" class="form-control" id="searchUser"
       placeholder="Rechercher par nom ou prénom..." 
       onkeyup="searchUsers()">
```

**À chaque frappe de touche**, la fonction `searchUsers()` est appelée.

### Exemple de Filtrage

**Utilisateurs dans la base :**
1. Admin User
2. benkhdi ja ahmed
3. ahmed loukil
4. 444 444
5. nagara nourpr

**Recherche : "ahmed"**

```javascript
filteredUsers = allUsers.filter(user => {
    const nom = user.nom.toLowerCase(); // "admin user", "benkhdi ja ahmed", etc.
    return nom.includes("ahmed");        // Vérifie si "ahmed" est dans le nom
});

// Résultat : filteredUsers = [
//   { nom: "benkhdi ja ahmed", ... },
//   { nom: "ahmed loukil", ... }
// ]
```

Ensuite, `displayUsersPage()` affiche uniquement ces 2 utilisateurs.

---

## 🎛️ Contrôles de Pagination

### Fonction `updatePaginationControls()` - Mise à Jour des Boutons

```javascript
// Ligne 153-165
function updatePaginationControls() {
    // 1. Calcul du nombre total de pages
    // Exemple : 12 utilisateurs / 5 par page = 2.4 → Math.ceil(2.4) = 3 pages
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    
    // 2. Calcul des indices pour l'affichage
    const startIndex = (currentPage - 1) * usersPerPage + 1;
    const endIndex = Math.min(currentPage * usersPerPage, filteredUsers.length);

    // 3. Mise à jour du texte d'information
    document.getElementById('paginationInfo').textContent =
        `Affichage ${startIndex}-${endIndex} sur ${filteredUsers.length} utilisateurs`;

    // 4. Activation/désactivation des boutons
    // Bouton "Précédent" désactivé si on est à la page 1
    document.getElementById('prevPage').disabled = currentPage === 1;
    
    // Bouton "Suivant" désactivé si on est à la dernière page
    document.getElementById('nextPage').disabled = currentPage >= totalPages;
}
```

### Fonctions de Navigation

```javascript
// Page précédente (ligne 168-174)
function previousPage() {
    if (currentPage > 1) {
        currentPage--;              // Décrémente la page
        displayUsersPage();         // Affiche la nouvelle page
        updatePaginationControls(); // Met à jour les boutons
    }
}

// Page suivante (ligne 177-184)
function nextPage() {
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    if (currentPage < totalPages) {
        currentPage++;              // Incrémente la page
        displayUsersPage();         // Affiche la nouvelle page
        updatePaginationControls(); // Met à jour les boutons
    }
}
```

---

## 🔐 Sécurité - Échappement HTML

Pour éviter les attaques XSS (Cross-Site Scripting), tous les textes provenant de la base de données sont échappés :

```javascript
// Ligne 243-248
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;  // Convertit automatiquement les caractères spéciaux
    return div.innerHTML;
}
```

**Exemple :**
- Entrée : `<script>alert('hack')</script>`
- Sortie : `&lt;script&gt;alert('hack')&lt;/script&gt;`

---

## 📝 Résumé du Flux Complet

1. **Chargement initial** → `loadUsers()` récupère tous les utilisateurs depuis `admin_api.php`
2. **Stockage** → `allUsers` contient tous les utilisateurs, `filteredUsers` = `allUsers`
3. **Affichage** → `displayUsersPage()` affiche les utilisateurs de l'index `(currentPage-1)*5` à `currentPage*5`
4. **Recherche** → `searchUsers()` filtre `allUsers` et met à jour `filteredUsers`
5. **Navigation** → `previousPage()` / `nextPage()` changent `currentPage` et réaffichent
6. **Mise à jour** → `updatePaginationControls()` active/désactive les boutons selon la page actuelle

---

## 🎯 Points Clés

- **Pagination côté client** : Tous les utilisateurs sont chargés une seule fois, puis la pagination se fait en JavaScript
- **Filtrage en temps réel** : La recherche se fait instantanément à chaque frappe
- **Performance** : Avec peu d'utilisateurs (< 1000), cette approche est efficace
- **Amélioration possible** : Pour des milliers d'utilisateurs, il faudrait faire la pagination côté serveur (dans `admin_api.php`)

---

## 📚 Fichiers Concernés

- **admin.html** : Structure HTML du tableau et des contrôles
- **admin.js** : Logique de pagination et filtrage (lignes 16-184)
- **admin_api.php** : API qui retourne tous les utilisateurs (action=users)
- **dashboard.css** : Styles pour le tableau et les boutons

---

*Créé le 2 décembre 2025 - GreenTechHub Administration*
