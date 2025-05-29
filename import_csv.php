<?php
// Vypnout všechny výstupy kromě našeho JSON
ob_start();

// Zapnout error reporting pouze do logu
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Zachytit všechny chyby
function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => "PHP Error: $errstr",
        'file' => basename($errfile),
        'line' => $errline,
        'debug' => 'Error handler triggered'
    ]);
    exit;
}

function handleException($exception) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
        'debug' => 'Exception handler triggered'
    ]);
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');

try {
    // Zkontrolovat metodu
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Pouze POST metoda je povolena');
    }
    
    // Zkontrolovat existenci souborů
    $requiredFiles = ['config.php', 'process_csv.php'];
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Požadovaný soubor '$file' neexistuje");
        }
    }
    
    // Načíst konfiguraci
    require_once 'config.php';
    require_once 'process_csv.php';
    
    // Zkontrolovat PDO
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Databázové připojení není správně nakonfigurováno');
    }
    
    // Test databázového připojení
    $pdo->query("SELECT 1");
    
    $processor = new CSVProcessor($pdo);
    
    // Najít CSV soubor
    $possibleNames = [
        'Objednavka prijata CSV - IMAGE CZECH.csv',
        'Objednávka přijatá CSV - IMAGE CZECH.csv',
        'objednavka prijata csv - image czech.csv'
    ];
    
    $csvFile = null;
    foreach ($possibleNames as $name) {
        if (file_exists($name)) {
            $csvFile = $name;
            break;
        }
    }
    
    if (!$csvFile) {
        // Najít všechny CSV soubory
        $allCsvFiles = glob('*.csv');
        if (empty($allCsvFiles)) {
            throw new Exception('Žádné CSV soubory nebyly nalezeny v root adresáři');
        }
        throw new Exception('CSV soubor s očekávaným názvem nebyl nalezen. Dostupné: ' . implode(', ', $allCsvFiles));
    }
    
    // Zkontrolovat, jestli je soubor čitelný
    if (!is_readable($csvFile)) {
        throw new Exception("CSV soubor '$csvFile' není čitelný");
    }
    
    // Zpracovat CSV
    $processedOrders = $processor->processCSVFile($csvFile);
    
    // Vyčistit output buffer a poslat JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'CSV import byl úspěšný',
        'processed_count' => count($processedOrders),
        'processed_orders' => array_slice($processedOrders, 0, 10), // Pouze prvních 10 pro výstup
        'csv_file_used' => basename($csvFile),
        'debug' => 'Import completed successfully'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'debug' => 'Exception caught in main try-catch'
    ]);
} catch (Error $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PHP Fatal Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'debug' => 'PHP Error caught'
    ]);
}

// Ukončit output buffering
ob_end_flush();
?>