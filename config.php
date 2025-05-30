<?php
// Nastavení kódování pro PHP
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Databázové připojení
$host = 'db.dw189.webglobe.com';
$dbname = 'vyroba_myrec_cz';
$username = 'vyroba_myrec_cz';
$password = 'bPgQY78S'; // Nahraďte skutečným heslem

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Explicitně nastavit kódování
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
} catch(PDOException $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}

// Nastavení časového pásma
date_default_timezone_set('Europe/Prague');
?>