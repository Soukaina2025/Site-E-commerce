<?php

define('DB_HOST', 'localhost:3307');         
define('DB_NAME', 'boutique');
define('DB_USER', 'root');
define('DB_PASS', '');    

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",DB_USER, DB_PASS );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
