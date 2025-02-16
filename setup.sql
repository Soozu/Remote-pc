-- Create the database
CREATE DATABASE IF NOT EXISTS remote_control;

-- Use the database
USE remote_control;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('computer', 'phone') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default computer user (password: computer123)
INSERT INTO users (username, password, role) 
VALUES ('computer', '$2y$10$YourHashedPasswordHere', 'computer');

-- Insert default phone user (password: phone123)
INSERT INTO users (username, password, role) 
VALUES ('phone', '$2y$10$YourHashedPasswordHere', 'phone'); 