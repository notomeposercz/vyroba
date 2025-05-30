<?php
/**
 * Simple script to check users in the database
 */
require_once 'config.php';

echo "🔍 Checking users in the database...\n";
echo "=====================================\n";

try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active, created_at, last_login FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ No users found in the database!\n";
        
        // Try to create a default admin user
        echo "🔧 Creating default admin user...\n";
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', $defaultPassword, 'Administrator', 'admin', 1]);
        
        echo "✅ Created admin user (username: admin, password: admin123)\n";
        
        // Fetch users again
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active, created_at, last_login FROM users ORDER BY id");
        $stmt->execute();
        $users = $stmt->fetchAll();
    }
    
    echo "👥 Found " . count($users) . " users:\n\n";
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? '✅ Active' : '❌ Inactive';
        $lastLogin = $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never';
        
        echo "🆔 ID: {$user['id']}\n";
        echo "👤 Username: {$user['username']}\n";
        echo "📝 Full Name: {$user['full_name']}\n";
        echo "🔰 Role: {$user['role']}\n";
        echo "📊 Status: $status\n";
        echo "🕐 Last Login: $lastLogin\n";
        echo "📅 Created: " . date('Y-m-d H:i:s', strtotime($user['created_at'])) . "\n";
        echo "---\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
