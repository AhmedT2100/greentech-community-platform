# 📧 GUIDE COMPLET - SYSTÈME DE MAILING & SÉCURITÉ

## 🎉 FONCTIONNALITÉS AJOUTÉES

Votre système dispose maintenant de :

1. **📧 Email de bienvenue** automatique lors de l'inscription
2. **🔐 Authentification à deux facteurs (2FA)** avec code par email
3. **🔑 Réinitialisation de mot de passe** par email

---

## 🚀 INSTALLATION (5 MINUTES)

### Étape 1 : Installer PHPMailer

Si ce n'est pas déjà fait, installez PHPMailer via Composer :

```bash
cd c:\xampp\htdocs\flow-tech-2a7-uzers
composer require phpmailer/phpmailer
```

### Étape 2 : Configurer Gmail SMTP

1. **Créer un mot de passe d'application Gmail** :
   - Allez sur https://myaccount.google.com/security
   - Activez la validation en deux étapes
   - Allez dans "Mots de passe des applications"
   - Générez un nouveau mot de passe pour "Mail"
   - Copiez le mot de passe généré (16 caractères)

2. **Modifier `MailManager.php`** (lignes 15-18) :
   ```php
   private $fromEmail = 'your-account@example.com'; // Votre email Gmail
   private $fromName = 'GreenTechHub';
   private $smtpUsername = 'your-account@example.com'; // Même email
   private $smtpPassword = '<configure privately>'; // Mot de passe d'application
   ```

### Étape 3 : Exécuter la migration

Ouvrez votre navigateur :
```
http://127.0.0.1:8080/migrate_2fa_reset.php
```

Vous devriez voir :
```
✅ Colonne 'two_factor_enabled' ajoutée
✅ Table 'two_factor_codes' créée
✅ Table 'password_reset_tokens' créée
✨ Migration terminée avec succès !
```

---

## 📧 1. EMAIL DE BIENVENUE

### Fonctionnement

L'email de bienvenue est envoyé **automatiquement** lors de l'inscription d'un nouvel utilisateur.

### Intégration dans votre code d'inscription

Modifiez votre fichier d'inscription (par exemple `auth.php` ou `signup.php`) :

```php
// Après avoir créé l'utilisateur
require_once 'MailManager.php';

$mailManager = new MailManager();
$mailManager->sendWelcomeEmail($email, $nom, $prenom);
```

### Exemple complet

```php
// Dans votre script d'inscription
if ($stmt->execute()) {
    $userId = $pdo->lastInsertId();
    
    // Envoyer l'email de bienvenue
    require_once 'MailManager.php';
    $mailManager = new MailManager();
    $mailManager->sendWelcomeEmail($email, $nom, $prenom);
    
    echo json_encode(['success' => true, 'message' => 'Inscription réussie !']);
}
```

### Contenu de l'email

L'email contient :
- 🌿 Message de bienvenue personnalisé
- ✅ Liste des fonctionnalités disponibles
- 🔗 Lien vers la plateforme
- 💡 Conseil pour activer la 2FA

---

## 🔐 2. AUTHENTIFICATION À DEUX FACTEURS (2FA)

### Comment ça marche ?

1. L'utilisateur active la 2FA dans son profil
2. À chaque connexion, un code à 6 chiffres est envoyé par email
3. L'utilisateur entre le code pour se connecter
4. Le code expire après 10 minutes

### Activer la 2FA pour un utilisateur

```javascript
// Dans votre interface utilisateur
async function enable2FA(userId) {
    const formData = new FormData();
    formData.append('action', 'enable2FA');
    formData.append('userId', userId);
    
    const response = await fetch('auth_api.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.success) {
        alert('2FA activé avec succès !');
    }
}
```

### Envoyer un code 2FA

```javascript
// Lors de la connexion d'un utilisateur avec 2FA activé
async function send2FACode(email) {
    const formData = new FormData();
    formData.append('action', 'send2FACode');
    formData.append('email', email);
    
    const response = await fetch('auth_api.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.success) {
        // Afficher un formulaire pour entrer le code
        showCodeInputForm();
    }
}
```

### Vérifier le code 2FA

```javascript
async function verify2FACode(email, code) {
    const formData = new FormData();
    formData.append('action', 'verify2FACode');
    formData.append('email', email);
    formData.append('code', code);
    
    const response = await fetch('auth_api.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.success) {
        // Connexion réussie
        window.location.href = 'dashboard.html';
    } else {
        alert('Code invalide ou expiré');
    }
}
```

### Flux de connexion avec 2FA

```javascript
// Exemple de flux complet
async function login(email, password) {
    // 1. Vérifier email et mot de passe
    const loginResponse = await verifyCredentials(email, password);
    
    if (loginResponse.success) {
        // 2. Vérifier si 2FA est activé
        if (loginResponse.twoFactorEnabled) {
            // 3. Envoyer le code 2FA
            await send2FACode(email);
            
            // 4. Afficher le formulaire de code
            showCodeInputForm();
        } else {
            // Connexion directe
            window.location.href = 'dashboard.html';
        }
    }
}
```

### Contenu de l'email 2FA

L'email contient :
- 🔐 Code à 6 chiffres
- ⏱️ Durée de validité (10 minutes)
- ⚠️ Conseils de sécurité

---

## 🔑 3. RÉINITIALISATION DE MOT DE PASSE

### Comment ça marche ?

1. L'utilisateur clique sur "Mot de passe oublié ?"
2. Il entre son email
3. Un lien de réinitialisation est envoyé par email
4. Le lien est valide pendant 1 heure
5. L'utilisateur crée un nouveau mot de passe

### Pages créées

- **`forgot-password.html`** - Demande de réinitialisation
- **`reset-password.html`** - Création du nouveau mot de passe

### Ajouter le lien "Mot de passe oublié"

Dans votre page de connexion (`index.html`), ajoutez :

```html
<div class="text-center mt-3">
    <a href="forgot-password.html" style="color: #4facfe;">
        Mot de passe oublié ?
    </a>
</div>
```

### Flux complet

1. **Utilisateur demande la réinitialisation** :
   - Va sur `forgot-password.html`
   - Entre son email
   - Reçoit un email avec un lien

2. **Utilisateur clique sur le lien** :
   - Arrive sur `reset-password.html?token=...`
   - Le token est vérifié automatiquement
   - Si valide, il peut créer un nouveau mot de passe

3. **Nouveau mot de passe créé** :
   - Le mot de passe est hashé et sauvegardé
   - Le token est marqué comme utilisé
   - L'utilisateur est redirigé vers la page de connexion

### Contenu de l'email de réinitialisation

L'email contient :
- 🔑 Lien de réinitialisation cliquable
- ⏱️ Durée de validité (1 heure)
- ⚠️ Informations de sécurité

---

## 🎨 PERSONNALISATION DES EMAILS

### Modifier les templates

Les templates HTML sont dans `MailManager.php` :

- `getWelcomeEmailTemplate()` - Email de bienvenue
- `get2FAEmailTemplate()` - Code 2FA
- `getPasswordResetEmailTemplate()` - Réinitialisation

### Exemple de personnalisation

```php
private function getWelcomeEmailTemplate($nom, $prenom) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            /* Vos styles personnalisés */
            .header { background: #votre-couleur; }
        </style>
    </head>
    <body>
        <!-- Votre contenu personnalisé -->
    </body>
    </html>
    ";
}
```

---

## 📊 STATISTIQUES D'EMAILS

Tous les emails envoyés sont enregistrés dans la table `email_stats` :

```sql
SELECT 
    email_type,
    COUNT(*) as total_sent,
    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened
FROM email_stats
GROUP BY email_type;
```

Vous pouvez voir ces stats dans l'onglet **"Métriques Avancées"** de votre back-office !

---

## 🔧 INTÉGRATION COMPLÈTE

### Exemple : Inscription avec email de bienvenue

```php
<?php
// signup.php
require_once 'config.php';
require_once 'MailManager.php';

$database = new Database();
$pdo = $database->getConnection();

$nom = $_POST['nom'];
$prenom = $_POST['prenom'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $password]);
    
    // Envoyer l'email de bienvenue
    $mailManager = new MailManager();
    $mailManager->sendWelcomeEmail($email, $nom, $prenom);
    
    echo json_encode(['success' => true, 'message' => 'Inscription réussie ! Vérifiez vos emails.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
```

### Exemple : Connexion avec 2FA

```javascript
// Dans votre fichier de connexion JavaScript
async function login(email, password) {
    // 1. Vérifier les identifiants
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    
    const response = await fetch('auth.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        // 2. Vérifier si 2FA est activé
        if (result.user.two_factor_enabled) {
            // Envoyer le code 2FA
            const codeFormData = new FormData();
            codeFormData.append('action', 'send2FACode');
            codeFormData.append('email', email);
            
            await fetch('auth_api.php', {
                method: 'POST',
                body: codeFormData
            });
            
            // Afficher le formulaire de code
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('codeForm').style.display = 'block';
        } else {
            // Connexion directe
            localStorage.setItem('user', JSON.stringify(result.user));
            window.location.href = 'dashboard.html';
        }
    }
}

async function verifyCode(email, code) {
    const formData = new FormData();
    formData.append('action', 'verify2FACode');
    formData.append('email', email);
    formData.append('code', code);
    
    const response = await fetch('auth_api.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        // Récupérer les infos utilisateur et connecter
        window.location.href = 'dashboard.html';
    } else {
        alert('Code invalide ou expiré');
    }
}
```

---

## 🐛 DÉPANNAGE

### Les emails ne sont pas envoyés

1. **Vérifiez les paramètres Gmail** dans `MailManager.php`
2. **Vérifiez que le mot de passe d'application est correct**
3. **Vérifiez les logs PHP** : `C:\xampp\php\logs\php_error_log`
4. **Testez la connexion SMTP** :

```php
// test_email.php
require_once 'MailManager.php';

$mailManager = new MailManager();
$sent = $mailManager->sendWelcomeEmail('test@example.com', 'Test', 'User');

if ($sent) {
    echo "Email envoyé avec succès !";
} else {
    echo "Erreur lors de l'envoi";
}
```

### Erreur "Class 'PHPMailer' not found"

Installez PHPMailer :
```bash
composer require phpmailer/phpmailer
```

### Les emails arrivent dans les spams

1. Configurez SPF et DKIM pour votre domaine
2. Utilisez un domaine personnalisé au lieu de Gmail
3. Évitez les mots "spam" dans le contenu

---

## 📁 FICHIERS CRÉÉS

### Backend
- ✅ `MailManager.php` - Gestionnaire d'emails
- ✅ `auth_api.php` - API pour 2FA et reset
- ✅ `migrate_2fa_reset.php` - Migration des tables

### Frontend
- ✅ `forgot-password.html` - Demande de réinitialisation
- ✅ `reset-password.html` - Création nouveau mot de passe

### Tables créées
- ✅ `two_factor_codes` - Codes 2FA
- ✅ `password_reset_tokens` - Tokens de réinitialisation
- ✅ Colonne `two_factor_enabled` dans `users`

---

## ✅ CHECKLIST

- [ ] PHPMailer installé
- [ ] Gmail SMTP configuré dans `MailManager.php`
- [ ] Migration `migrate_2fa_reset.php` exécutée
- [ ] Email de bienvenue intégré dans l'inscription
- [ ] Lien "Mot de passe oublié" ajouté à la page de connexion
- [ ] 2FA testé avec un utilisateur
- [ ] Réinitialisation de mot de passe testée

---

## 🎉 RÉSUMÉ

Vous disposez maintenant d'un **système complet de sécurité et de mailing** :

✅ **Email de bienvenue** automatique
✅ **2FA par email** avec codes à 6 chiffres
✅ **Réinitialisation de mot de passe** sécurisée
✅ **Templates HTML professionnels**
✅ **Statistiques d'emails** dans les métriques
✅ **Pages modernes** et responsive

**Profitez de votre système sécurisé ! 🔐📧**
