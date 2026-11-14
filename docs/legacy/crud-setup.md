# Configuration du Système CRUD Utilisateurs GreenTechHub

## Structure des Fichiers Créés

```
accounts/
├── Controller/
│   └── UserController.php       (Logique métier et vérification admin)
├── Model/
│   └── User.php                 (Classe modèle utilisateur)
└── View/
    ├── Backoffice/
    │   ├── addUser.php          (Formulaire d'ajout)
    │   ├── readUser.php         (Liste des utilisateurs)
    │   ├── updateUser.php       (Formulaire de modification)
    │   └── deleteUser.php       (Page de confirmation suppression)
    └── Frontoffice/
        (À implémenter pour l'affichage des profils publics)

uploads/
└── profile_pictures/            (Dossier pour les photos de profil)
```

## Modifications Effectuées

### 1. **Admin.html**
   - ✅ Ajout de la vérification d'authentification au chargement
   - ✅ Redirection si l'utilisateur n'est pas admin
   - ✅ Lien vers "Gestion Utilisateurs" dans le menu

### 2. **Admin.js**
   - ✅ Vérification du rôle admin au chargement
   - ✅ Redirection automatique si non admin

### 3. **Web.js**
   - ✅ Removed auto-login behavior to admin panel
   - ✅ Admin button only shows for admin users

### 4. **Clé de Sécurité**
   - ✅ UserController.php vérifie le rôle admin pour TOUTES les opérations CRUD

## Instructions d'Installation

### Étape 1: Mise à Jour de la Base de Données
Exécutez cette requête SQL via phpMyAdmin:

```sql
ALTER TABLE `utilisateur` ADD COLUMN `photo_profil` VARCHAR(255) NULL DEFAULT NULL AFTER `role`;
```

Ou importez le fichier: `migration_add_photo_profil.sql`

### Étape 2: Vérifier les Permissions

Assurez-vous que le dossier `uploads/profile_pictures/` est inscriptible:
- Windows: Le dossier doit avoir les permissions d'écriture
- Linux: chmod 755 uploads/profile_pictures/

### Étape 3: Accéder aux Pages

- **Admin Panel**: http://localhost/sssss/admin.html
  - Requis: Connexion avec compte admin
  - Non-admin users: Redirection vers dashboard.html
  
- **Gestion des Utilisateurs**: http://localhost/sssss/accounts/View/Backoffice/readUser.php
  - Requis: Connexion avec compte admin
  - Affiche la liste complète des utilisateurs

### Étape 4: Fonctionnalités CRUD

#### Créer un Utilisateur
```
POST /accounts/Controller/UserController.php
action=create
nom=Jean Dupont
email=jean@example.com
mot_de_passe=password123
role=user
photo_profil=file (optionnel)
```

#### Lire les Utilisateurs
```
GET /accounts/Controller/UserController.php?action=read
GET /accounts/Controller/UserController.php?action=read&id=1
```

#### Mettre à Jour un Utilisateur
```
POST /accounts/Controller/UserController.php
action=update
id_user=1
nom=Jean Dupont
email=jean@example.com
role=user
photo_profil=file (optionnel)
```

#### Supprimer un Utilisateur
```
POST /accounts/Controller/UserController.php
action=delete
id_user=1
```

#### Upload de Photo de Profil
```
POST /accounts/Controller/UserController.php
action=upload_photo
id_user=1
photo=file (JPG, PNG, GIF - Max 5MB)
```

## Contrôle d'Accès

**Tous les endpoints CRUD nécessitent:**
1. Une session valide ($_SESSION['user_id'])
2. Un rôle admin ($_SESSION['user_role'] === 'admin')

Sinon: Réponse JSON `{"success": false, "message": "Accès refusé - droits d'administrateur requis"}`

## Limitation Admin

- Les administrateurs ne peuvent pas supprimer leur propre compte via le CRUD
- Les emails doivent être uniques
- Les modifications d'email sont vérifiées pour éviter les doublons

## Support des Photos

- **Format supporté**: JPG, PNG, GIF
- **Taille max**: 5MB
- **Stockage**: /uploads/profile_pictures/user_{id}_{timestamp}.{ext}
- **Base de données**: Colonne `photo_profil` enregistre le nom du fichier

## Dépannage

### Erreur: "Accès refusé - droits d'administrateur requis"
→ Vérifiez que vous êtes connecté avec un compte admin

### Erreur: "Utilisateur non trouvé"
→ Vérifiez que l'ID utilisateur existe en base de données

### Erreur: "Cet email est déjà utilisé"
→ Utilisez un email différent ou modifiez l'utilisateur existant

### Erreur lors de l'upload de photo
→ Vérifiez que le dossier `uploads/profile_pictures/` existe et est inscriptible

## Notes Importantes

1. **Session**: Le système utilise les sessions PHP côté serveur
2. **LocalStorage**: Les infos utilisateur sont aussi stockées en localStorage pour l'interface
3. **Sécurité**: Les mots de passe sont hashés avec password_hash()
4. **Validation**: Les emails sont validés et escapés pour prévenir les injections
