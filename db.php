<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "CALEN");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure UTF-8 for emoji reactions and text
$conn->set_charset("utf8mb4");

// Create PDO connection for prepared statements
$pdo = new PDO("mysql:host=localhost;dbname=CALEN;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES utf8mb4");
?>
