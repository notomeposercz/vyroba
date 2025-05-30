<?php
/**
 * Skript pro opravu objednávky 25J-00376
 * Nastaví preview_approved_date na aktuální datum, aby se objednávka zobrazila v kalendáři
 */

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Najít objednávku 25J-00376
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->execute(['25J-00376']);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "CHYBA: Objednávka 25J-00376 nebyla nalezena.\n";
        exit(1);
    }
    
    echo "Nalezena objednávka:\n";
    echo "ID: " . $order['id'] . "\n";
    echo "Kód: " . $order['order_code'] . "\n";
    echo "Preview status: " . $order['preview_status'] . "\n";
    echo "Preview approved date: " . ($order['preview_approved_date'] ?? 'NULL') . "\n";
    echo "Shipping date: " . ($order['shipping_date'] ?? 'NULL') . "\n";
    echo "\n";
    
    // Zkontrolovat, zda už má preview_approved_date
    if ($order['preview_approved_date']) {
        echo "Objednávka už má nastavené preview_approved_date: " . $order['preview_approved_date'] . "\n";
        echo "Oprava není potřebná.\n";
        exit(0);
    }
    
    // Zkontrolovat, zda má preview_status = "Schváleno"
    if ($order['preview_status'] !== 'Schváleno') {
        echo "CHYBA: Objednávka nemá preview_status = 'Schváleno' (má: '" . $order['preview_status'] . "')\n";
        echo "Nelze nastavit preview_approved_date.\n";
        exit(1);
    }
    
    // Nastavit preview_approved_date na aktuální datum
    $currentDate = date('Y-m-d');
    
    echo "Nastavuji preview_approved_date na: $currentDate\n";
    
    $updateStmt = $pdo->prepare("UPDATE orders SET preview_approved_date = ?, updated_at = NOW() WHERE id = ?");
    $result = $updateStmt->execute([$currentDate, $order['id']]);
    
    if ($result) {
        echo "✅ ÚSPĚCH: Objednávka 25J-00376 byla aktualizována.\n";
        echo "Preview approved date nastaven na: $currentDate\n";
        
        // Ověřit změnu
        $checkStmt = $pdo->prepare("SELECT preview_approved_date FROM orders WHERE id = ?");
        $checkStmt->execute([$order['id']]);
        $updatedOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Ověření: preview_approved_date = " . $updatedOrder['preview_approved_date'] . "\n";
        
        // Vypočítat období zobrazení v kalendáři
        $displayFrom = $updatedOrder['preview_approved_date'];
        $displayTo = $order['shipping_date'] ? $order['shipping_date'] : date('Y-m-d', strtotime($displayFrom . ' + 14 days'));
        
        echo "\n📅 Objednávka se nyní bude zobrazovat v kalendáři:\n";
        echo "Od: $displayFrom\n";
        echo "Do: $displayTo\n";
        
    } else {
        echo "❌ CHYBA: Nepodařilo se aktualizovat objednávku.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ CHYBA DATABÁZE: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ CHYBA: " . $e->getMessage() . "\n";
    exit(1);
}
?>
