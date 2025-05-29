<?php
// Autentifikační systém
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        die('Nemáte oprávnění pro přístup k této stránce.');
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function logUserAction($pdo, $tableName, $recordId, $action, $oldValues = null, $newValues = null, $description = null) {
    if (!isset($_SESSION['user_id'])) return;
    
    $stmt = $pdo->prepare("
        INSERT INTO change_history (user_id, table_name, record_id, action, old_values, new_values, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $tableName,
        $recordId,
        $action,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $description
    ]);
}

function getRoleDisplayName($role) {
    $roles = [
        'admin' => 'Administrátor',
        'obchodnik' => 'Obchodník',
        'vyroba' => 'Výroba',
        'grafik' => 'Grafik'
    ];
    
    return $roles[$role] ?? $role;
}

function getRolePermissions($role) {
    $permissions = [
        'admin' => [
            'view_orders', 'edit_orders', 'delete_orders',
            'view_schedule', 'edit_schedule',
            'view_users', 'edit_users',
            'view_history', 'manage_system'
        ],
        'obchodnik' => [
            'view_orders', 'edit_orders',
            'view_schedule',
            'view_history'
        ],
        'vyroba' => [
            'view_orders',
            'view_schedule', 'edit_schedule',
            'view_history'
        ],
        'grafik' => [
            'view_orders', 'edit_preview_status',
            'view_schedule',
            'view_history'
        ]
    ];
    
    return $permissions[$role] ?? [];
}

function hasPermission($permission) {
    if (!isset($_SESSION['role'])) return false;
    
    $userPermissions = getRolePermissions($_SESSION['role']);
    return in_array($permission, $userPermissions);
}
?>