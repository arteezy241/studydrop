<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP is working!</h2>";
echo "<p>PHP version: " . phpversion() . "</p>";

// Test env variables
echo "<p>DB_HOST: " . getenv('DB_HOST') . "</p>";
echo "<p>DB_NAME: " . getenv('DB_NAME') . "</p>";
echo "<p>DB_USER: " . getenv('DB_USER') . "</p>";

// Test DB connection
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME'));
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    echo "<p style='color:green'>Database connected!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>DB Error: " . $e->getMessage() . "</p>";
}