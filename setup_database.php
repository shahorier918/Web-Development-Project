<?php
require_once 'config/database.php';

try {
    // Create tables
    $sql = file_get_contents('database.sql');
    $pdo->exec($sql);
    echo "Database tables created successfully!";
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
} 