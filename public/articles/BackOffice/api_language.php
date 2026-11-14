<?php
require_once __DIR__ . '/../app/utils/Language.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    
    if (Language::set($lang)) {
        echo json_encode(['success' => true, 'language' => $lang]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'current' => Language::getCurrentLanguage(),
        'available' => Language::getAvailableLanguages()
    ]);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
