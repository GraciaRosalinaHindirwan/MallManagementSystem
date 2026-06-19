<?php
// Copy this file to config/db.php and update the DSN/user/pass for your environment.
// Example: in XAMPP MySQL default user is root with empty password.

// PDO connection example
try {
    $dsn = 'mysql:host=127.0.0.1;dbname=mall_db;charset=utf8mb4';
    $dbUser = 'root';
    $dbPass = '';
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    // If connection fails, remove or fix config/db.php
    error_log('DB connection failed: ' . $e->getMessage());
    // Do not throw to keep fallback to session-based storage
}

/*
SQL schema suggestions (run in phpMyAdmin or CLI):

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(32) NOT NULL UNIQUE,
  type VARCHAR(16) NOT NULL,
  owner_name VARCHAR(255),
  ticket VARCHAR(64),
  entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  type VARCHAR(16) DEFAULT 'regular'
);

CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE,
  slots INT DEFAULT 0,
  package VARCHAR(32) DEFAULT 'basic',
  discount INT DEFAULT 20
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NULL,
  plate VARCHAR(32),
  type VARCHAR(16),
  owner_name VARCHAR(255),
  entry_time TIMESTAMP,
  exit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  duration_minutes INT,
  base_tariff INT,
  discount_percent INT,
  discount_amount INT,
  total INT
);

*/

?>
