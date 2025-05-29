<?php
// Konfigurace databáze
define('DB_HOST', 'db.dw189.webglobe.com');
define('DB_NAME', 'vyroba_myrec_cz');
define('DB_USER', 'vyroba_myrec_cz');
define('DB_PASS', 'bPgQY78S');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}
?>