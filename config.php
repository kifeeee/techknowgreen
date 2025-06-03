<?php
// Database configuration
$host = 'localhost';
$dbname = 'techknowgreen_shop';
$username = 'techknowgreen_B1';
$password = 'Langat@22';

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Try to establish a database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    http_response_code(500);
    exit;
}