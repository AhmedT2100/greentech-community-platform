<?php
require_once __DIR__ . '/../config/environment.php';
// test_inscription.php - Test complet d'inscription + email

echo "<h1>Test d'inscription GreenTechHub</h1>";

// Données de test
$data = http_build_query([
    'action' => 'signup',
    'prenom' => 'Jean',
    'nom' => 'Dupont',
    'email' => app_env('TEST_EMAIL', 'test@example.invalid'), // Remplacez par un email réel
    'password' => app_env('TEST_PASSWORD')
]);

// Configuration de la requête
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://localhost/GreenTechHub/auth.php', false, $context);

if ($result === FALSE) {
    echo "<p style='color: red;'>❌ Erreur de connexion au serveur</p>";
} else {
    $response = json_decode($result, true);
    
    if ($response['success']) {
        echo "<p style='color: green;'>✅ " . $response['message'] . "</p>";
        echo "<p><strong>Utilisateur créé :</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $response['user']['id'] . "</li>";
        echo "<li>Nom: " . $response['user']['nom'] . "</li>";
        echo "<li>Email: " . $response['user']['email'] . "</li>";
        echo "<li>Rôle: " . $response['user']['role'] . "</li>";
        echo "</ul>";
        echo "<p>📧 <strong>Vérifiez votre boîte email !</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ " . $response['message'] . "</p>";
    }
}

echo "<hr>";
echo "<h2>Dépannage :</h2>";
echo "<ul>";
echo "<li>Vérifiez que votre serveur web fonctionne</li>";
echo "<li>Vérifiez les permissions des fichiers</li>";
echo "<li>Vérifiez la configuration de la base de données</li>";
echo "<li>Contactez votre hébergeur pour l'envoi d'emails</li>";
echo "</ul>";
?>