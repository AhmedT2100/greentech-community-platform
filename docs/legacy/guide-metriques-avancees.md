# 📊 GUIDE D'UTILISATION - MÉTRIQUES AVANCÉES

## 🚀 INSTALLATION RAPIDE

### Étape 1 : Exécuter la migration (OBLIGATOIRE)
Ouvrez votre navigateur et accédez à :
```
http://127.0.0.1:8080/migrate_advanced_metrics.php
```

✅ Cette migration va créer les tables nécessaires :
- `user_sessions` - Suivi des sessions utilisateurs
- `daily_metrics` - Métriques quotidiennes agrégées
- `user_events` - Événements utilisateurs
- `email_stats` - Statistiques d'emails
- `generated_reports` - Rapports générés
- `kpi_targets` - Objectifs et KPIs

### Étape 2 : Accéder à l'interface
```
http://127.0.0.1:8080/admin.html
```

Connectez-vous avec un compte **administrateur**, puis cliquez sur l'onglet **"Métriques Avancées"**.

---

## 📈 FONCTIONNALITÉS DISPONIBLES

### 1. 📊 MÉTRIQUES EN TEMPS RÉEL

#### Cartes de Statistiques
- **Actifs 7j** : Utilisateurs actifs sur les 7 derniers jours
- **Sessions Aujourd'hui** : Nombre de sessions du jour
- **Durée Moyenne** : Temps moyen passé par session (en minutes)
- **Taux Rétention** : Pourcentage d'utilisateurs qui reviennent

### 2. 📈 GRAPHIQUES ANALYTIQUES

#### Croissance des Utilisateurs
- Visualisation de la croissance sur 7, 30 ou 90 jours
- Affiche les nouveaux utilisateurs et le total cumulé
- Graphique en ligne avec zones remplies

#### Sessions Quotidiennes
- Nombre de sessions par jour
- Utilisateurs uniques par jour
- Graphique en barres sur 7 ou 30 jours

### 3. 🎯 OBJECTIFS & KPIs

Les KPIs suivants sont suivis automatiquement :
- **Nouveaux utilisateurs** (objectif mensuel)
- **Taux d'engagement** (% d'utilisateurs actifs)
- **Taux de rétention** (% d'utilisateurs qui reviennent)
- **Innovations postées** (nombre mensuel)

Chaque KPI affiche :
- Progression en pourcentage
- Valeur actuelle / Valeur cible
- Barre de progression colorée (vert = 100%, bleu = 75%+, orange = 50%+, rouge < 50%)

### 4. 📱 RÉPARTITION PAR APPAREIL

Graphique circulaire montrant la distribution des sessions par type d'appareil :
- Desktop
- Mobile
- Tablet

### 5. 📧 PERFORMANCE DES EMAILS

Tableau détaillé avec :
- Type d'email (welcome, ban, unban, notification, etc.)
- Nombre d'emails envoyés
- Nombre d'emails ouverts
- Taux d'ouverture (%)

### 6. 🌍 RÉPARTITION GÉOGRAPHIQUE

Tableau montrant :
- Pays d'origine
- Ville
- Nombre de sessions
- Utilisateurs uniques

### 7. 🔴 ACTIVITÉ EN TEMPS RÉEL

Flux d'activité des 5 dernières minutes :
- Connexions/déconnexions
- Inscriptions
- Modifications de profil
- Publications d'innovations
- Nombre d'utilisateurs en ligne actuellement

**Actualisation automatique** toutes les 30 secondes !

### 8. 📥 EXPORT DE RAPPORTS

Exportez vos données en CSV :
1. Choisissez le type de rapport (Général, Utilisateurs, Sessions, Emails)
2. Sélectionnez la période (date début - date fin)
3. Cliquez sur "Exporter CSV"
4. Le fichier sera téléchargé automatiquement

Les rapports sont sauvegardés dans le dossier `reports/`.

---

## 🔧 INTÉGRATION AVEC VOTRE APPLICATION

### Tracker une Session Utilisateur

Ajoutez ce code lors de la connexion d'un utilisateur :

```javascript
// Démarrer une session
async function startUserSession(userId) {
    const formData = new FormData();
    formData.append('action', 'startSession');
    formData.append('userId', userId);
    
    const response = await fetch('metrics_api.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.success) {
        // Sauvegarder l'ID de session
        localStorage.setItem('sessionId', result.sessionId);
    }
}

// Terminer une session
async function endUserSession() {
    const sessionId = localStorage.getItem('sessionId');
    if (!sessionId) return;
    
    const formData = new FormData();
    formData.append('action', 'endSession');
    formData.append('sessionId', sessionId);
    
    await fetch('metrics_api.php', {
        method: 'POST',
        body: formData
    });
    
    localStorage.removeItem('sessionId');
}

// Appeler lors de la connexion
startUserSession(user.id);

// Appeler lors de la déconnexion
window.addEventListener('beforeunload', endUserSession);
```

### Tracker un Événement

```javascript
async function trackEvent(userId, eventType, eventCategory, eventData) {
    const formData = new FormData();
    formData.append('action', 'trackEvent');
    formData.append('userId', userId);
    formData.append('eventType', eventType);
    formData.append('eventCategory', eventCategory);
    formData.append('eventData', JSON.stringify(eventData));
    
    await fetch('metrics_api.php', {
        method: 'POST',
        body: formData
    });
}

// Exemples d'utilisation
trackEvent(userId, 'login', 'authentication', {});
trackEvent(userId, 'innovation_post', 'content', { innovationId: 123 });
trackEvent(userId, 'profile_update', 'user_action', { field: 'email' });
```

### Enregistrer une Statistique d'Email

Modifiez votre système d'envoi d'emails pour enregistrer les stats :

```php
// Après l'envoi d'un email
$stmt = $pdo->prepare("INSERT INTO email_stats (user_id, email_type, recipient_email, subject, sent_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, 'welcome', $email, $subject]);
```

---

## 📊 MÉTRIQUES QUOTIDIENNES AUTOMATIQUES

Pour calculer automatiquement les métriques quotidiennes, créez un fichier `cron_daily_metrics.php` :

```php
<?php
require_once 'config.php';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$today = date('Y-m-d');

// Compter les utilisateurs
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$today'")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE DATE(session_start) = '$today'")->fetchColumn();
$bannedUsers = $pdo->query("SELECT COUNT(*) FROM user_bans WHERE ban_until IS NULL OR ban_until > NOW()")->fetchColumn();

// Compter les sessions
$totalSessions = $pdo->query("SELECT COUNT(*) FROM user_sessions WHERE DATE(session_start) = '$today'")->fetchColumn();
$avgDuration = $pdo->query("SELECT AVG(duration_seconds) FROM user_sessions WHERE DATE(session_start) = '$today' AND session_end IS NOT NULL")->fetchColumn();

// Compter les logins
$totalLogins = $pdo->query("SELECT COUNT(*) FROM user_events WHERE event_type = 'login' AND DATE(created_at) = '$today'")->fetchColumn();
$failedLogins = $pdo->query("SELECT COUNT(*) FROM user_events WHERE event_type = 'login_failed' AND DATE(created_at) = '$today'")->fetchColumn();

// Compter les emails
$emailsSent = $pdo->query("SELECT COUNT(*) FROM email_stats WHERE DATE(sent_at) = '$today'")->fetchColumn();

// Insérer ou mettre à jour
$stmt = $pdo->prepare("INSERT INTO daily_metrics (metric_date, total_users, new_users, active_users, banned_users, total_sessions, avg_session_duration, total_logins, failed_logins, emails_sent) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    total_users = VALUES(total_users),
    new_users = VALUES(new_users),
    active_users = VALUES(active_users),
    banned_users = VALUES(banned_users),
    total_sessions = VALUES(total_sessions),
    avg_session_duration = VALUES(avg_session_duration),
    total_logins = VALUES(total_logins),
    failed_logins = VALUES(failed_logins),
    emails_sent = VALUES(emails_sent)");

$stmt->execute([$today, $totalUsers, $newUsers, $activeUsers, $bannedUsers, $totalSessions, $avgDuration, $totalLogins, $failedLogins, $emailsSent]);

echo "Métriques quotidiennes calculées avec succès pour $today\n";
?>
```

Configurez un cron job pour exécuter ce script tous les jours :
```bash
0 0 * * * php /path/to/cron_daily_metrics.php
```

---

## 🎨 PERSONNALISATION

### Modifier les Objectifs KPI

Accédez à la base de données et modifiez la table `kpi_targets` :

```sql
UPDATE kpi_targets 
SET target_value = 200 
WHERE kpi_name = 'Nouveaux utilisateurs';
```

### Ajouter un Nouveau KPI

```sql
INSERT INTO kpi_targets (kpi_name, target_value, period_type, start_date, end_date) 
VALUES ('Taux de conversion', 15.5, 'monthly', CURDATE(), LAST_DAY(CURDATE()));
```

---

## 🐛 DÉPANNAGE

### Les graphiques ne s'affichent pas
1. Vérifiez que Chart.js est bien chargé (ouvrez la console du navigateur)
2. Assurez-vous que la migration a été exécutée
3. Vérifiez que `metrics_api.php` est accessible

### Aucune donnée dans les métriques
1. Les tables sont vides car aucune donnée n'a été trackée
2. Intégrez le tracking des sessions et événements (voir section Intégration)
3. Attendez que des utilisateurs se connectent

### Erreur "Class not found"
1. Vérifiez que `config.php` existe et contient les bonnes informations de connexion
2. Assurez-vous que les tables ont été créées

---

## 📚 API DISPONIBLES

Toutes les APIs sont accessibles via `metrics_api.php?action=NOM_ACTION`

### GET Requests
- `getDashboardMetrics` - Métriques du dashboard
- `getUserGrowth&days=30` - Croissance des utilisateurs
- `getActiveUsers` - Utilisateurs actifs par période
- `getSessionStats&days=7` - Statistiques de sessions
- `getEmailStats` - Statistiques d'emails
- `getDeviceStats` - Statistiques par appareil
- `getGeolocationStats` - Statistiques géographiques
- `getKPIProgress` - Progression des KPIs
- `getRealtimeActivity` - Activité en temps réel

### POST Requests
- `trackEvent` - Enregistrer un événement
- `startSession` - Démarrer une session
- `endSession` - Terminer une session
- `exportReport` - Exporter un rapport

---

## ✨ RÉSUMÉ

Vous avez maintenant un système complet de métriques avancées qui vous permet de :

✅ Suivre la croissance de votre plateforme
✅ Analyser le comportement des utilisateurs
✅ Mesurer la performance de vos emails
✅ Définir et suivre des objectifs
✅ Voir l'activité en temps réel
✅ Exporter des rapports détaillés

**Tout est intégré dans votre back-office existant `admin.html` !**

---

## 🆘 SUPPORT

En cas de problème :
1. Vérifiez les logs PHP
2. Ouvrez la console du navigateur (F12)
3. Assurez-vous que toutes les tables ont été créées
4. Vérifiez les permissions de la base de données

**Bon analytics ! 📊🚀**
