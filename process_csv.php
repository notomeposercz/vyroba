<?php
require_once 'config.php';

class CSVProcessor {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function processCSVFile($csvFile) {
        if (!file_exists($csvFile)) {
            throw new Exception("CSV soubor neexistuje: $csvFile");
        }
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception("Nelze otevřít CSV soubor");
        }
        
        // Přeskočit hlavičku
        $header = fgetcsv($handle, 1000, ';');
        
        $processedOrders = [];
        
        while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
            if (count($data) >= 6) {
                $orderData = [
                    'order_code' => trim($data[0]),
                    'catalog' => trim($data[1]),
                    'quantity' => (int)$data[2],
                    'order_date' => $this->parseDate($data[3]),
                    'goods_ordered_date' => $this->parseDate($data[4]),
                    'goods_stocked_date' => $this->parseDate($data[5])
                ];
                
                $this->insertOrUpdateOrder($orderData);
                $processedOrders[] = $orderData['order_code'];
            }
        }
        
        fclose($handle);
        return $processedOrders;
    }
    
    private function parseDate($dateString) {
        if (empty(trim($dateString))) {
            return null;
        }
        
        // Formát DD.MM.YYYY
        $date = DateTime::createFromFormat('d.m.Y', trim($dateString));
        return $date ? $date->format('Y-m-d') : null;
    }
    
    private function insertOrUpdateOrder($orderData) {
        $sql = "INSERT INTO orders (order_code, catalog, quantity, order_date, goods_ordered_date, goods_stocked_date)
                VALUES (:order_code, :catalog, :quantity, :order_date, :goods_ordered_date, :goods_stocked_date)
                ON DUPLICATE KEY UPDATE
                catalog = VALUES(catalog),
                quantity = VALUES(quantity),
                order_date = VALUES(order_date),
                goods_ordered_date = VALUES(goods_ordered_date),
                goods_stocked_date = VALUES(goods_stocked_date)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($orderData);
    }
}

// Použití
try {
    $processor = new CSVProcessor($pdo);
    $csvFile = 'Objednávka přijatá CSV - IMAGE CZECH.csv';
    $processedOrders = $processor->processCSVFile($csvFile);
    
    echo "Úspěšně zpracováno " . count($processedOrders) . " objednávek:\n";
    foreach ($processedOrders as $orderCode) {
        echo "- $orderCode\n";
    }
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage() . "\n";
}
?>