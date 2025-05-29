<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

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
            default:
                http_response_code(404);
                return ['error' => 'Endpoint not found'];
        }
    }
    
    private function handleOrders($method) {
        switch ($method) {
            case 'GET':
                return $this->getOrders();
            case 'PUT':
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
    
    private function updateOrder() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $sql = "UPDATE orders SET 
                preview_status = :preview_status,
                preview_approved_date = :preview_approved_date,
                shipping_date = :shipping_date,
                production_status = :production_status
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($input);
    }
    
    private function handleSchedule($method) {
        switch ($method) {
            case 'GET':
                return $this->getSchedule();
            case 'POST':
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