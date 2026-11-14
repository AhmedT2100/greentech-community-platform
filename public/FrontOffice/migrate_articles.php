<?php
/**
 * Web-Based Anonymous Article Migration Tool
 */

require_once __DIR__ . '/../config.php';

$message = '';
$success = false;

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($password)) {
        $message = '❌ All fields are required!';
    } else if (strlen($password) < 6) {
        $message = '❌ Password must be at least 6 characters!';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ Invalid email format!';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if user already exists
        $checkStmt = $db->prepare("SELECT id FROM admins WHERE email = :email");
        $checkStmt->execute([':email' => $email]);
        
        if ($checkStmt->rowCount() > 0) {
            $message = '❌ User with this email already exists!';
        } else {
            // Hash password and create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '_', $full_name)) . '_' . uniqid();
            
            try {
                $insertStmt = $db->prepare("
                    INSERT INTO admins (username, email, password, full_name, is_active) 
                    VALUES (:username, :email, :password, :full_name, 1)
                ");
                $insertStmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':full_name' => $full_name
                ]);
                $newUserId = $db->lastInsertId();
                
                // Update anonymous articles
                $updateStmt = $db->prepare("UPDATE articles SET user_id = :user_id, user_name = :user_name WHERE user_id IS NULL OR user_id = '' OR user_name = 'Anonymous'");
                $updateStmt->execute([
                    ':user_id' => $newUserId,
                    ':user_name' => $full_name
                ]);
                $rowsUpdated = $updateStmt->rowCount();
                
                $success = true;
                $message = "✅ Account created successfully! $rowsUpdated article(s) are now assigned to you.";
            } catch (PDOException $e) {
                $message = '❌ Error: ' . $e->getMessage();
            }
        }
    }
}

// Get PDO connection to check anonymous articles
$database = new Database();
$db = $database->getConnection();

// Get count of anonymous articles
$countStmt = $db->query("SELECT COUNT(*) as count FROM articles WHERE user_id IS NULL OR user_id = '' OR user_name = 'Anonymous'");
$anonCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrate Anonymous Articles - GreenTechHub</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300|Nunito:400,700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
        }
        
        .migration-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .migration-container h1 {
            color: #353535;
            margin-bottom: 10px;
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
        }
        
        .migration-container p {
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #353535;
            font-family: 'Nunito', sans-serif;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-migrate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-migrate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-family: 'Nunito', sans-serif;
        }
        
        .alert-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #1565c0;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #656565;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1>🔐 Create Account for Anonymous Articles</h1>
        <p>Convert your anonymous articles to an owned account</p>
        
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p>📝 Found <strong><?= $anonCount ?></strong> anonymous article(s) waiting to be claimed.</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Adem Author" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="adem@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter a strong password" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-migrate">✅ Create Account & Migrate Articles</button>
        </form>
    </div>
</body>
</html>
