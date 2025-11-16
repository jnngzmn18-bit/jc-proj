<?php
$DB_HOST = 'hopper.proxy.rlwy.net'; // from Railway
$DB_PORT = '53461';                 // from Railway
$DB_NAME = 'railway';               // from Railway
$DB_USER = 'root';                  // from Railway
$DB_PASS = 'jnZBSnZBhtvApLHLBJplanxuIRvMLDdE'; // copy the password from Railway dashboard

try {
    $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}
?>