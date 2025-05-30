<?php
require_once 'config.php';

class CSVProcessor {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function processCSVFile($csvFile) {
        if (!file_exists($csvFile)) {
            throw new Exception('CSV soubor neexistuje: ' . $csvFile);
        }
        
        $content = file_get_contents($csvFile);
        if ($content === false) {
            throw new Exception('Nelze přečíst CSV soubor');
        }
        
        $content = $this->fixEncoding($content);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
        file_put_contents($tempFile, $content);
        
        try {
            $result = $this->processUTF8CSV($tempFile);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
        
        return $result;
    }
    
    private function fixEncoding($content) {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }
        
        $converted = @iconv('WINDOWS-1250', 'UTF-8//IGNORE', $content);
        if ($converted !== false && !empty($converted)) {
            return $converted;
        }
        
        $converted = @iconv('ISO-8859-2', 'UTF-8//IGNORE', $content);
        if ($converted !== false && !empty($converted)) {
            return $converted;
        }
        
        return $content;
    }
    
    private function processUTF8CSV($csvFile) {
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception('Nelze otevřít CSV soubor');
        }
        
        $header = fgetcsv($handle, 1000, ';');
        
        $processedOrders = array();
        $ignoredCount = 0;
        $rowNumber = 1;
        
        while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
            $rowNumber++;
            
            if (count($data) >= 6) {
                try {
                    $catalog = $this->cleanString($data[1]);
                    
                    if ($this->shouldIgnoreRow($catalog)) {
                        $ignoredCount++;
                        continue;
                    }
                    
                    $orderCode = $this->cleanString($data[0]);
                    
                    if (empty($orderCode)) {
                        continue;
                    }
                    
                    $orderData = array(
                        'order_code' => $orderCode,
                        'catalog' => $catalog,
                        'quantity' => $this->parseQuantity($data[2]),
                        'order_date' => $this->parseDate($data[3]),
                        'goods_ordered_date' => $this->parseDate($data[4]),
                        'goods_stocked_date' => $this->parseDate($data[5])
                    );
                    
                    $this->insertOrUpdateOrder($orderData);
                    $processedOrders[] = $orderData['order_code'];
                    
                } catch (Exception $e) {
                    error_log('Chyba při zpracování řádku ' . $rowNumber . ': ' . $e->getMessage());
                    continue;
                }
            }
        }
        
        fclose($handle);
        
        return array(
            'processed_orders' => $processedOrders,
            'ignored_count' => $ignoredCount,
            'total_processed' => count($processedOrders)
        );
    }
    
    private function cleanString($string) {
        $cleaned = trim($string);
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        if (strlen($cleaned) > 100) {
            $cleaned = substr($cleaned, 0, 100);
        }
        
        return $cleaned;
    }
    
    private function parseQuantity($quantityString) {
        $quantity = (int)preg_replace('/[^0-9]/', '', trim($quantityString));
        return $quantity > 0 ? $quantity : 1;
    }
    
    private function shouldIgnoreRow($catalog) {
        $normalizedCatalog = strtolower(trim($catalog));
        
        if (strpos($normalizedCatalog, 'poštovné') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'postovne') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'štovné') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'stovne') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'dopravné') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'dopravne') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'pvy') !== false) {
            return true;
        }
        if (strpos($normalizedCatalog, 'program') !== false) {
            return true;
        }
        
        return false;
    }
    
    private function parseDate($dateString) {
        $dateString = trim($dateString);
        if (empty($dateString)) {
            return null;
        }
        
        $date = DateTime::createFromFormat('d.m.Y', $dateString);
        if ($date) {
            return $date->format('Y-m-d');
        }
        
        $date = DateTime::createFromFormat('d/m/Y', $dateString);
        if ($date) {
            return $date->format('Y-m-d');
        }
        
        $date = DateTime::createFromFormat('Y-m-d', $dateString);
        if ($date) {
            return $date->format('Y-m-d');
        }
        
        return null;
    }
    
    private function insertOrUpdateOrder($orderData) {
        if (strlen($orderData['order_code']) > 50) {
            throw new Exception('Kód objednávky je příliš dlouhý');
        }
        
        if (strlen($orderData['catalog']) > 100) {
            $orderData['catalog'] = substr($orderData['catalog'], 0, 100);
        }
        
        $sql = 'INSERT INTO orders (order_code, catalog, quantity, order_date, goods_ordered_date, goods_stocked_date, created_at)
                VALUES (:order_code, :catalog, :quantity, :order_date, :goods_ordered_date, :goods_stocked_date, NOW())
                ON DUPLICATE KEY UPDATE
                catalog = VALUES(catalog),
                quantity = VALUES(quantity),
                order_date = VALUES(order_date),
                goods_ordered_date = VALUES(goods_ordered_date),
                goods_stocked_date = VALUES(goods_stocked_date),
                updated_at = NOW()';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($orderData);
    }
}
?>