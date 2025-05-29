<?php
require_once 'config.php';

// Vytvoř správné hashe a vlož uživatele
$password = "heslo123";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Generuji hash pro heslo: $password\n";
echo "Hash: $hash\n\n";

// Definice uživatelů
$users = [
    ['admin', 'admin@vyroba.cz', 'Správce Systému', 'admin'],
    ['pavel.novak', 'pavel.novak@vyroba.cz', 'Pavel Novák', 'obchodnik'],
    ['marie.svoboda', 'marie.svoboda@vyroba.cz', 'Marie Svobodová', 'obchodnik'],
    ['jan.dvorak', 'jan.dvorak@vyroba.cz', 'Jan Dvořák', 'vyroba'],
    ['tomas.krejci', 'tomas.krejci@vyroba.cz', 'Tomáš Krejčí', 'vyroba'],
    ['anna.horak', 'anna.horak@vyroba.cz', 'Anna Horáková', 'grafik'],
    ['petr.vesely', 'petr.vesely@vyroba.cz', 'Petr Veselý', 'grafik']
];

try {
    // Smaž stávající uživatele
    $pdo->exec("DELETE FROM users");
    echo "Staří uživatelé smazáni.\n";
    
    // Vlož nové uživatele
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($users as $user) {
        $stmt->execute([$user[0], $user[1], $hash, $user[2], $user[3]]);
        echo "Vytvořen uživatel: {$user[0]} ({$user[2]}) - Role: {$user[3]}\n";
    }
    
    echo "\n✓ Všichni uživatelé byli úspěšně vytvořeni!\n";
    echo "Heslo pro všechny účty: $password\n\n";
    
    // Ověř že vše funguje
    echo "=== Test přihlášení ===\n";
    $testStmt = $pdo->prepare("SELECT username, full_name, role FROM users WHERE username = ?");
    $testStmt->execute(['admin']);
    $testUser = $testStmt->fetch();
    
    if ($testUser) {
        echo "✓ Test uživatel nalezen: {$testUser['full_name']}\n";
        
        // Test hesla
        $passStmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $passStmt->execute(['admin']);
        $storedHash = $passStmt->fetchColumn();
        
        if (password_verify($password, $storedHash)) {
            echo "✓ Hash hesla je správný!\n";
        } else {
            echo "✗ Problém s hashem hesla!\n";
        }
    } else {
        echo "✗ Test uživatel nenalezen!\n";
    }
    
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage() . "\n";
}
?>