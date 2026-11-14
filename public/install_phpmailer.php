<?php
/**
 * Installation manuelle de PHPMailer
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Installation PHPMailer</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>📦 Installation de PHPMailer</h2>";

// Créer le dossier vendor/phpmailer/src
$dirs = ['vendor', 'vendor/phpmailer', 'vendor/phpmailer/src'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "<p class='success'>✅ Dossier '$dir' créé</p>";
    } else {
        echo "<p class='info'>ℹ️ Dossier '$dir' existe déjà</p>";
    }
}

// Télécharger les fichiers PHPMailer depuis GitHub
$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
];

echo "<h3>📥 Téléchargement des fichiers...</h3>";

foreach ($files as $filename => $url) {
    echo "<p>Téléchargement de $filename...</p>";

    $content = @file_get_contents($url);

    if ($content === false) {
        echo "<p class='error'>❌ Erreur lors du téléchargement de $filename</p>";
        continue;
    }

    $filepath = "vendor/phpmailer/src/$filename";
    $saved = file_put_contents($filepath, $content);

    if ($saved) {
        echo "<p class='success'>✅ $filename téléchargé (" . number_format(strlen($content)) . " octets)</p>";
    } else {
        echo "<p class='error'>❌ Erreur lors de la sauvegarde de $filename</p>";
    }
}

echo "<h3 style='color: green;'>✨ Installation terminée !</h3>";
echo "<p><a href='test_email.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Tester l'envoi d'email</a></p>";
echo "</div></body></html>";
?>