<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "remote_control";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $conn->exec($sql);
    
    // Switch to the new database
    $conn->exec("USE $dbname");

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('computer', 'phone') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Create default users
    $computerPass = password_hash("computer123", PASSWORD_DEFAULT);
    $phonePass = password_hash("phone123", PASSWORD_DEFAULT);

    // Insert default computer user
    $sql = "INSERT IGNORE INTO users (username, password, role) VALUES 
            ('computer', :password, 'computer')";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['password' => $computerPass]);

    // Insert default phone user
    $sql = "INSERT IGNORE INTO users (username, password, role) VALUES 
            ('phone', :password, 'phone')";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['password' => $phonePass]);

    echo "Database and users created successfully!<br>";
    echo "Computer login: username='computer', password='computer123'<br>";
    echo "Phone login: username='phone', password='phone123'";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?> 