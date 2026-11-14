<?php
// test_reclamation_email.php - Test if reclamation emails work

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'config.php';
include_once 'email_helper.php';

echo "<h1>Testing Reclamation Email System</h1>";

// Check database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Database connection failed!");
}
echo "✓ Database connected<br>";

// Check if PHPMailer files exist
$files = [
    'vendor/phpmailer/src/Exception.php',
    'vendor/phpmailer/src/PHPMailer.php',
    'vendor/phpmailer/src/SMTP.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ Found: $file<br>";
    } else {
        echo "❌ Missing: $file<br>";
    }
}

// Check if a test user exists with email
echo "<br><h2>Checking Users with Emails:</h2>";
$stmt = $db->query("SELECT id_user, nom, email FROM utilisateur WHERE email IS NOT NULL AND email != '' LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($users) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    foreach ($users as $user) {
        echo "<tr><td>{$user['id_user']}</td><td>{$user['nom']}</td><td>{$user['email']}</td></tr>";
    }
    echo "</table><br>";
    
    // Test sending email to first user
    $testUser = $users[0];
    echo "<h2>Attempting to send test email to: {$testUser['email']}</h2>";
    
    $result = sendReclamationCreatedEmail(
        $testUser['email'],
        $testUser['nom'],
        999,
        'Test Reclamation Subject'
    );
    
    if ($result) {
        echo "<p style='color: green; font-size: 18px;'>✓ Email sent successfully!</p>";
        echo "<p>Check your inbox: {$testUser['email']}</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>✗ Email sending failed!</p>";
        echo "<p>Check Apache error log for details</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No users with email addresses found in database!</p>";
    echo "<p>Please make sure users have email addresses in the utilisateur table.</p>";
}
?>
