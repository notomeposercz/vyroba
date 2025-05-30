<?php
// Tracy debugger - komentováno kvůli problémům na serveru
// require_once __DIR__ . '/tracy.phar';
// use Tracy\Debugger;
// Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/log');

// Vlastní logging systém
function writeLog($message, $level = 'INFO') {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    error_log($logEntry, 3, $logFile);
}

// Nastavení PHP pro logování
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Zachytit všechny chyby a výjimky
set_error_handler(function($severity, $message, $file, $line) {
    writeLog("PHP Error: $message in $file:$line", 'ERROR');
    return false; // Nechej PHP pokračovat v normálním zpracování
});

set_exception_handler(function($exception) {
    writeLog("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine(), 'FATAL');
});

require_once 'config.php';
require_once 'auth.php';

// Vyžadovat přihlášení pro API
requireLogin();

class ProductionAPI {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function handleRequest() {
        writeLog("=== Nový API request ===", 'DEBUG');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        $endpoint = $pathParts[array_search('api.php', $pathParts) + 1] ?? '';
        
        writeLog("Method: $method, Path: $path, Endpoint: $endpoint", 'DEBUG');
        writeLog("Full URI: " . $_SERVER['REQUEST_URI'], 'DEBUG');
        
        switch ($endpoint) {
            case 'orders':
                return $this->handleOrders($method);
            case 'schedule':
                writeLog("Handling schedule endpoint", 'DEBUG');
                return $this->handleSchedule($method);
            case 'technologies':
                return $this->handleTechnologies($method);
            case 'history':
                return $this->handleHistory($method);
            case 'blocks':  // NOVÝ ENDPOINT
                return $this->handleBlocks($method);
            default:
                writeLog("Endpoint '$endpoint' not found", 'WARNING');
                http_response_code(404);
                return ['error' => 'Endpoint not found'];
        }
    }
    
    // PŘIDAT TUTO NOVOU METODU
    private function handleBlocks($method) {
        switch ($method) {
            case 'GET':
                return $this->getBlocks();
            case 'POST':
                if (!hasPermission('edit_schedule')) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->createBlock();
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }
    
    // PŘIDAT TUTO METODU
    private function getBlocks() {
        $startDate = $_GET['start'] ?? date('Y-m-d');
        $endDate = $_GET['end'] ?? date('Y-m-d', strtotime('+30 days'));
        
        $sql = "SELECT * FROM calendar_blocks 
                WHERE start_date <= :end_date AND end_date >= :start_date
                ORDER BY start_date";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        
        return $stmt->fetchAll();
    }
    
    private function createBlock() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validace vstupů
    if (!isset($input['type']) || !isset($input['start_date']) || !isset($input['end_date'])) {
        http_response_code(400);
        return ['error' => 'Chybí povinné údaje'];
    }
    
    $sql = "INSERT INTO calendar_blocks (type, start_date, end_date, note, created_by, created_at)
            VALUES (:type, :start_date, :end_date, :note, :created_by, NOW())";
    
    $data = [
        'type' => $input['type'],
        'start_date' => $input['start_date'],
        'end_date' => $input['end_date'],
        'note' => $input['note'] ?? null,
        'created_by' => $_SESSION['user_id']
    ];
    
    try {
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result) {
            $blockId = $this->pdo->lastInsertId();
            logUserAction($this->pdo, 'calendar_blocks', $blockId, 'INSERT', null, $data, 'Vytvořena nová blokace');
            return ['success' => true, 'id' => $blockId];
        } else {
            return ['success' => false, 'error' => 'Chyba při ukládání'];
        }
    } catch (PDOException $e) {
        error_log('Chyba při ukládání blokace: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Databázová chyba'];
    }
}
    
    // NAHRADIT metodu updateOrder:
private function updateOrder($orderId) {
    if (!hasPermission('edit_orders')) {
        http_response_code(403);
        return ['error' => 'Insufficient permissions'];
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Načíst starý záznam pro log
    $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $oldValues = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldValues) {
        return ['success' => false, 'message' => 'Objednávka nebyla nalezena'];
    }
    
    // Sestavit SQL dynamicky na základě poskytnutých dat
    $updateFields = [];
    $data = ['id' => $orderId];
    
    $allowedFields = [
        'order_code', 'catalog', 'quantity', 'order_date', 'goods_ordered_date', 
        'goods_stocked_date', 'shipping_date', 'preview_status', 'preview_approved_date',
        'production_status', 'notes', 'salesperson', 'technology_id'
    ];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $input)) {
            $updateFields[] = "$field = :$field";
            $data[$field] = $input[$field];
        }
    }
    
    if (empty($updateFields)) {
        return ['success' => false, 'message' => 'Žádná data k aktualizaci'];
    }
    
    $sql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = :id";
    
    try {
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result) {
            // Připravit data pro log
            $newValues = array_merge($oldValues, $data);
            unset($newValues['id']); // ID se nemění
            
            logUserAction($this->pdo, 'orders', $orderId, 'UPDATE', $oldValues, $newValues, 'Aktualizována objednávka');
            
            return ['success' => true, 'message' => 'Objednávka byla aktualizována'];
        } else {
            return ['success' => false, 'message' => 'Chyba při aktualizaci objednávky'];
        }
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Chyba databáze při aktualizaci'];
    }
}
    
    // Zbytek kódu zůstává stejný...
    private function handleOrders($method) {
        switch ($method) {
            case 'GET':
                if (!hasPermission('view_orders')) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->getOrders();
            case 'POST':
                if (!hasPermission('edit_orders')) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->createOrder();
            case 'PUT':
                if (!hasPermission('edit_orders')) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->updateOrder();
            default:
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function getOrders() {
        $status = $_GET['status'] ?? 'all';
        $sql = "SELECT o.*, t.name as technology_name, t.color as technology_color
                FROM orders o
                LEFT JOIN production_schedule ps ON o.id = ps.order_id
                LEFT JOIN technologies t ON ps.technology_id = t.id";
        if ($status !== 'all') {
            $sql .= " WHERE o.production_status = :status";
        }
        $sql .= " ORDER BY o.order_date DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($status !== 'all') {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            http_response_code(500);
            return ['success' => false, 'error' => 'Chyba při SELECT orders: ' . $e->getMessage()];
        }
    }
    
    private function createOrder() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $sql = "INSERT INTO orders (order_code, catalog, quantity, order_date, goods_ordered_date, goods_stocked_date, preview_status, production_status, notes, salesperson)
                VALUES (:order_code, :catalog, :quantity, :order_date, :goods_ordered_date, :goods_stocked_date, :preview_status, :production_status, :notes, :salesperson)";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($input);
        
        if ($result) {
            $orderId = $this->pdo->lastInsertId();
            logUserAction($this->pdo, 'orders', $orderId, 'INSERT', null, $input, 'Vytvořena nová objednávka');
        }
        
        return ['success' => $result, 'id' => $orderId ?? null];
    }
    
    private function handleHistory($method) {
        if ($method === 'GET') {
            if (!hasPermission('view_history')) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions'];
            }
            return $this->getHistory();
        }
        
        http_response_code(405);
        return ['error' => 'Method not allowed'];
    }
    
    private function getHistory() {
        $tableName = $_GET['table'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        $sql = "SELECT ch.*, u.full_name as user_name
                FROM change_history ch
                JOIN users u ON ch.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($tableName) {
            $sql .= " AND ch.table_name = :table_name";
            $params['table_name'] = $tableName;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(ch.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(ch.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        
        $sql .= " ORDER BY ch.created_at DESC LIMIT 100";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    private function handleSchedule($method) {
        writeLog("handleSchedule called with method: $method", 'DEBUG');
        
        switch ($method) {
            case 'GET':
                writeLog("Handling GET schedule request", 'DEBUG');
                return $this->getSchedule();
            case 'POST':
                writeLog("Handling POST schedule request", 'DEBUG');
                if (!hasPermission('edit_schedule')) {
                    writeLog("Permission denied for edit_schedule", 'WARNING');
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->createScheduleEntry();
            default:
                writeLog("Method $method not allowed for schedule", 'WARNING');
                http_response_code(405);
                return ['error' => 'Method not allowed'];
        }
    }
    
    private function getSchedule() {
        $startDate = $_GET['start'] ?? date('Y-m-d');
        $endDate = $_GET['end'] ?? date('Y-m-d', strtotime('+7 days'));
        
        $sql = "SELECT ps.*, o.order_code, o.quantity, t.name as technology_name, t.color
                FROM production_schedule ps
                JOIN orders o ON ps.order_id = o.id
                JOIN technologies t ON ps.technology_id = t.id
                WHERE ps.start_date <= :end_date AND ps.end_date >= :start_date
                ORDER BY ps.start_date";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        
        return $stmt->fetchAll();
    }
    
    private function createScheduleEntry() {
        writeLog("=== Začátek createScheduleEntry ===", 'DEBUG');
        
        try {
            // Načíst vstupní data
            $input = json_decode(file_get_contents('php://input'), true);
            writeLog("Vstupní data: " . json_encode($input), 'DEBUG');
            
            if (!$input) {
                writeLog("Chyba: Žádná vstupní data nebo neplatný JSON", 'ERROR');
                http_response_code(400);
                return ['success' => false, 'error' => 'Neplatná vstupní data'];
            }
            
            // Validace požadovaných polí
            if (!isset($input['order_id']) || !isset($input['planned_date'])) {
                writeLog("Chyba: Chybí order_id nebo planned_date", 'ERROR');
                http_response_code(400);
                return ['success' => false, 'error' => 'Chybí order_id nebo planned_date'];
            }
            
            $orderId = (int)$input['order_id'];
            $plannedDate = $input['planned_date'];
            $duration = isset($input['estimated_duration']) ? (int)$input['estimated_duration'] : 1;
            $notes = array_key_exists('notes', $input) ? $input['notes'] : '';
            
            writeLog("Zpracovávané hodnoty - Order ID: $orderId, Planned date: $plannedDate, Duration: $duration", 'DEBUG');
            
            // Načíst objednávku
            $stmt = $this->pdo->prepare('SELECT technology FROM orders WHERE id = :order_id');
            $stmt->execute(['order_id' => $orderId]);
            $order = $stmt->fetch();
            
            if (!$order || !$order['technology']) {
                writeLog("Chyba: Objednávka s ID $orderId neexistuje nebo nemá technologii", 'ERROR');
                http_response_code(400);
                return ['success' => false, 'error' => 'Objednávka nebo technologie nenalezena'];
            }
            
            writeLog("Objednávka nalezena s technologií: " . $order['technology'], 'DEBUG');
            
            // Najít ID technologie
            $stmt = $this->pdo->prepare('SELECT id FROM technologies WHERE name = :name');
            $stmt->execute(['name' => $order['technology']]);
            $tech = $stmt->fetch();
            
            if (!$tech) {
                writeLog("Chyba: Technologie '{$order['technology']}' nenalezena", 'ERROR');
                http_response_code(400);
                return ['success' => false, 'error' => 'Technologie nenalezena'];
            }
            
            $technologyId = $tech['id'];
            writeLog("Technologie ID: $technologyId", 'DEBUG');
            
            $startDate = $plannedDate;
            $endDate = date('Y-m-d', strtotime("$plannedDate +" . max(1, $duration-1) . " days"));
            
            writeLog("Start date: $startDate, End date: $endDate", 'DEBUG');
            
            // Vložit do production_schedule
            $sql = "INSERT INTO production_schedule (order_id, start_date, end_date, technology_id, is_locked) VALUES (:order_id, :start_date, :end_date, :technology_id, 0)";
            $stmt = $this->pdo->prepare($sql);
            
            writeLog("SQL: $sql", 'DEBUG');
            writeLog("Parametry: order_id=$orderId, start_date=$startDate, end_date=$endDate, technology_id=$technologyId", 'DEBUG');
            
            $result = $stmt->execute([
                'order_id' => $orderId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'technology_id' => $technologyId
            ]);
            
            if ($result) {
                writeLog("Úspěšně vloženo do production_schedule", 'INFO');
                writeLog("=== Konec createScheduleEntry - ÚSPĚCH ===", 'DEBUG');
                return ['success' => true];
            } else {
                writeLog("Chyba při vkládání - execute() vrátilo false", 'ERROR');
                writeLog("Error info: " . json_encode($stmt->errorInfo()), 'ERROR');
                http_response_code(500);
                return [
                    'success' => false,
                    'error' => 'Chyba při ukládání do plánu',
                    'errorInfo' => $stmt->errorInfo(),
                    'input' => [
                        'order_id' => $orderId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'technology_id' => $technologyId,
                        'notes' => $notes
                    ]
                ];
            }
        } catch (PDOException $e) {
            writeLog("PDO Exception v createScheduleEntry: " . $e->getMessage(), 'FATAL');
            writeLog("PDO Code: " . $e->getCode(), 'FATAL');
            http_response_code(500);
            return ['success' => false, 'error' => 'Databázová výjimka: ' . $e->getMessage()];
        } catch (Throwable $e) {
            writeLog("Obecná výjimka v createScheduleEntry: " . $e->getMessage(), 'FATAL');
            writeLog("Stack trace: " . $e->getTraceAsString(), 'FATAL');
            http_response_code(500);
            return ['success' => false, 'error' => 'Obecná výjimka: ' . $e->getMessage()];
        }
    }
    
    private function handleTechnologies($method) {
        if ($method === 'GET') {
            try {
                $stmt = $this->pdo->query("SELECT * FROM technologies ORDER BY name");
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                http_response_code(500);
                return ['success' => false, 'error' => 'Chyba při SELECT technologies: ' . $e->getMessage()];
            }
        }
        http_response_code(405);
        return ['error' => 'Method not allowed'];
    }
}

$api = new ProductionAPI($pdo);
$response = $api->handleRequest();

writeLog("API response: " . json_encode($response), 'DEBUG');
writeLog("HTTP response code: " . http_response_code(), 'DEBUG');

echo json_encode($response);
?>