<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$servername", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS remote_control";
    $pdo->exec($sql);
    echo "Database created successfully<br>";
    
    // Switch to the database
    $pdo->exec("USE remote_control");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('computer', 'phone') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Users table created successfully<br>";
    
    // Create default users
    // First, check if users exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username IN ('computer', 'phone')");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Create computer user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
        $stmt->execute([
            'username' => 'computer',
            'password' => password_hash('computer123', PASSWORD_DEFAULT),
            'role' => 'computer'
        ]);
        
        // Create phone user
        $stmt->execute([
            'username' => 'phone',
            'password' => password_hash('phone123', PASSWORD_DEFAULT),
            'role' => 'phone'
        ]);
        
        echo "Default users created successfully<br>";
        echo "Computer login: username='computer', password='computer123'<br>";
        echo "Phone login: username='phone', password='phone123'<br>";
    } else {
        echo "Users already exist<br>";
    }
    
    echo "<br>Setup completed successfully!<br>";
    echo "<a href='login.php' class='btn btn-primary'>Go to Login Page</a>";
    
} catch(PDOException $e) {
    echo "<br>Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .setup-container {
            max-width: 600px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h2 class="mb-4">Database Setup</h2>
        <div class="alert alert-info">
            <strong>Note:</strong> After setup is complete, you can log in with these credentials:
            <ul class="mt-2">
                <li>Computer Account: username='computer', password='computer123'</li>
                <li>Phone Account: username='phone', password='phone123'</li>
            </ul>
        </div>
    </div>
</body>
</html> 