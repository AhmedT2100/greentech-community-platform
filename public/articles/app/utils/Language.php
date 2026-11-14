<?php

class Language {
    private static $language = 'fr';
    
    private static $translations = [
        'fr' => [
            'dashboard' => 'Tableau de bord',
            'articles' => 'Articles',
            'comments' => 'Commentaires',
            'reports' => 'Signalements',
            'admins' => 'Administrateurs',
            'banned' => 'Utilisateurs bannis',
            'analytics' => 'Analytiques',
            'activity_logs' => 'Journaux d\'activité',
            'admin_status' => 'Statut',
            'logout' => 'Déconnexion',
            'welcome' => 'Bienvenue, %s',
            'articles_published' => 'Articles publiés',
            'comments' => 'Commentaires',
            'pending_reports' => 'Signalements en attente',
            'administrators' => 'Administrateurs',
            'recent_articles' => 'Articles récents',
            'recent_reports' => 'Signalements récents',
            'admin_dashboard' => 'Tableau de bord administrateur',
            'manage_admins_title' => 'Gérer les administrateurs',
            'manage_articles_title' => 'Gérer les articles',
            'banned_users_title' => 'Utilisateurs bannis',
            'language' => 'Langue',
            'french' => 'Français',
            'english' => 'Anglais',
            'save' => 'Enregistrer',
            'delete' => 'Supprimer',
            'edit' => 'Modifier',
            'add' => 'Ajouter',
            'create' => 'Créer',
            'cancel' => 'Annuler',
            'confirm' => 'Confirmer',
            'search' => 'Rechercher',
            'filter' => 'Filtrer',
            'no_results' => 'Aucun résultat',
            'loading' => 'Chargement...',
            'error' => 'Erreur',
            'success' => 'Succès',
            'warning' => 'Avertissement',
            'info' => 'Information',
        ]
    ];
    
    public static function get($key, $default = '') {
        $lang = self::$language;
        if (isset(self::$translations[$lang][$key])) {
            return self::$translations[$lang][$key];
        }
        return $default !== '' ? $default : $key;
    }
    
    public static function getCurrentLanguage() {
        return self::$language;
    }
    
    public static function set($language) {
        if (in_array($language, ['fr', 'en'])) {
            self::$language = $language;
            return true;
        }
        return false;
    }
    
    public static function getAvailableLanguages() {
        return ['fr', 'en'];
    }
}
