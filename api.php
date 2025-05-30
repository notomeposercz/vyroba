<?php
require_once 'config.php';
require_once 'auth.php';

// Vyžadovat přihlášení pro API
requireLogin();

class ProductionAPI {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        $endpoint = $pathParts[array_search('api.php', $pathParts) + 1] ?? '';
        
        switch ($endpoint) {
            case 'orders':
                return $this->handleOrders($method);
            case 'schedule':
                return $this->handleSchedule($method);
            case 'technologies':
                return $this->handleTechnologies($method);
            case 'history':
                return $this->handleHistory($method);
            case 'blocks':  // NOVÝ ENDPOINT
                return $this->handleBlocks($method);
            default:
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
    
    // OPRAVIT UPDATEORDER METODU - NAHRADIT EXISTUJÍCÍ
    private function updateOrder() {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['id'];
        
        // Získat původní hodnoty
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $oldValues = $stmt->fetch();
        
        // Kontrola oprávnění pro stav náhledu
        if (isset($input['preview_status']) && !hasPermission('edit_preview_status') && $_SESSION['role'] !== 'admin') {
            unset($input['preview_status']);
        }
        
        // ROZŠÍŘIT SQL O VŠECHNA POLE
        $sql = "UPDATE orders SET 
                order_code = :order_code,
                catalog = :catalog,
                quantity = :quantity,
                order_date = :order_date,
                goods_ordered_date = :goods_ordered_date,
                goods_stocked_date = :goods_stocked_date,
                preview_status = :preview_status,
                preview_approved_date = :preview_approved_date,
                shipping_date = :shipping_date,
                production_status = :production_status,
                notes = :notes,
                salesperson = :salesperson
                WHERE id = :id";
        
        // Připravit data s výchozími hodnotami
        $data = [
            'id' => $orderId,
            'order_code' => $input['order_code'] ?? $oldValues['order_code'],
            'catalog' => $input['catalog'] ?? $oldValues['catalog'],
            'quantity' => $input['quantity'] ?? $oldValues['quantity'],
            'order_date' => $input['order_date'] ?? $oldValues['order_date'],
            'goods_ordered_date' => $input['goods_ordered_date'] ?? $oldValues['goods_ordered_date'],
            'goods_stocked_date' => $input['goods_stocked_date'] ?? $oldValues['goods_stocked_date'],
            'preview_status' => $input['preview_status'] ?? $oldValues['preview_status'],
            'preview_approved_date' => $input['preview_approved_date'] ?? $oldValues['preview_approved_date'],
            'shipping_date' => $input['shipping_date'] ?? $oldValues['shipping_date'],
            'production_status' => $input['production_status'] ?? $oldValues['production_status'],
            'notes' => $input['notes'] ?? $oldValues['notes'],
            'salesperson' => $input['salesperson'] ?? $oldValues['salesperson']
        ];
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($data);
        
        if ($result) {
            logUserAction($this->pdo, 'orders', $orderId, 'UPDATE', $oldValues, $data, 'Aktualizována objednávka');
        }
        
        return ['success' => $result];
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
        
        $stmt = $this->pdo->prepare($sql);
        if ($status !== 'all') {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
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
        switch ($method) {
            case 'GET':
                return $this->getSchedule();
            case 'POST':
                if (!hasPermission('edit_schedule')) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
                return $this->createScheduleEntry();
            default:
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
    
    private function handleTechnologies($method) {
        if ($method === 'GET') {
            $stmt = $this->pdo->query("SELECT * FROM technologies ORDER BY name");
            return $stmt->fetchAll();
        }
        
        http_response_code(405);
        return ['error' => 'Method not allowed'];
    }
}

$api = new ProductionAPI($pdo);
echo json_encode($api->handleRequest());
?>