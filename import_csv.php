<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function handleError($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'error' => 'PHP Error: ' . $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ));
    exit;
}

function handleException($exception) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => false,
        'error' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ));
    exit;
}

set_error_handler('handleError');
set_exception_handler('handleException');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Pouze POST metoda je povolena');
    }
    
    require_once 'config.php';
    require_once 'process_csv.php';
    
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Databázové připojení není správně nakonfigurováno');
    }
    
    $pdo->query('SELECT 1');
    $pdo->exec('SET NAMES utf8mb4');
    
    $processor = new CSVProcessor($pdo);
    
    $possibleNames = array(
        'Objednavka prijata CSV - IMAGE CZECH.csv',
        'Objednávka přijatá CSV - IMAGE CZECH.csv',
        'objednavka prijata csv - image czech.csv',
        'Objednavka prijata CSV - IMAGE CZECH.CSV'
    );
    
    $csvFile = null;
    foreach ($possibleNames as $name) {
        if (file_exists($name)) {
            $csvFile = $name;
            break;
        }
    }
    
    if (!$csvFile) {
        $allCsvFiles = glob('*.csv');
        if (empty($allCsvFiles)) {
            throw new Exception('Žádné CSV soubory nebyly nalezeny');
        }
        throw new Exception('CSV soubor s očekávaným názvem nebyl nalezen. Dostupné: ' . implode(', ', $allCsvFiles));
    }
    
    if (!is_readable($csvFile)) {
        throw new Exception('CSV soubor ' . $csvFile . ' není čitelný');
    }
    
    $result = $processor->processCSVFile($csvFile);
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => true,
        'message' => 'CSV import byl úspěšný',
        'processed_count' => $result['total_processed'],
        'ignored_count' => $result['ignored_count'],
        'csv_file_used' => basename($csvFile)
    ), JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
?>