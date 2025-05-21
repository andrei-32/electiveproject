<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'rps_gameb_rps_game');
define('DB_USER', 'rpsgameb_admin');
define('DB_PASS', 'L~EOkr@Gf.[]');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 