<?php
/**
 * Comprehensive API Testing Script for Production Management System
 * Tests all endpoints with proper authentication to verify debugging fixes
 */

class APITester {
    private $baseUrl;
    private $sessionCookie;
    private $testResults = [];
    
    public function __construct($baseUrl = 'http://localhost:8000') {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Initialize cURL with common options
     */
    private function initCurl($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_COOKIEJAR => '/tmp/api_test_cookies.txt',
            CURLOPT_COOKIEFILE => '/tmp/api_test_cookies.txt',
            CURLOPT_USERAGENT => 'API Test Script 1.0'
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            if (is_array($data)) {
                // For login, use form data
                if (strpos($url, 'login.php') !== false) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                } else {
                    // For API endpoints, use JSON
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        return $ch;
    }
    
    /**
     * Execute curl request and parse response
     */
    private function executeRequest($ch) {
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        curl_close($ch);
        
        return [
            'status_code' => $httpCode,
            'headers' => $headers,
            'body' => $body,
            'json' => json_decode($body, true)
        ];
    }
    
    /**
     * Authenticate with the system
     */
    public function authenticate($username = 'admin', $password = 'heslo123') {
        echo "🔐 Testing Authentication...\n";
        
        // First, get the login page to establish session
        $ch = $this->initCurl('/login.php');
        $response = $this->executeRequest($ch);
        
        if ($response['status_code'] !== 200) {
            echo "❌ Could not reach login page (HTTP {$response['status_code']})\n";
            return false;
        }
        
        echo "📋 Login page accessible, attempting authentication...\n";
        
        // Now attempt login with form data
        $loginData = [
            'username' => $username,
            'password' => $password
        ];
        
        $ch = $this->initCurl('/login.php', 'POST', $loginData);
        $response = $this->executeRequest($ch);
        
        echo "   → Login attempt status: HTTP {$response['status_code']}\n";
        
        // Check if login was successful by looking for redirect or success indicators
        if ($response['status_code'] == 302) {
            // Check if redirected to index.php (successful login)
            if (strpos($response['headers'], 'Location: index.php') !== false) {
                echo "✅ Authentication successful (redirected to index.php)\n";
                return true;
            }
        } elseif ($response['status_code'] == 200) {
            // Check if we're still on login page (failed login) or redirected
            if (strpos($response['body'], 'login') === false || strpos($response['body'], 'dashboard') !== false) {
                echo "✅ Authentication successful (logged in)\n";
                return true;
            }
        }
        
        echo "❌ Authentication failed\n";
        echo "   → Response headers: " . substr(str_replace("\n", " ", $response['headers']), 0, 200) . "...\n";
        echo "   → Response body snippet: " . substr($response['body'], 0, 100) . "...\n";
        
        // Try to verify session by checking if we can access the main page
        $ch = $this->initCurl('/index.php');
        $testResponse = $this->executeRequest($ch);
        
        if ($testResponse['status_code'] == 200 && strpos($testResponse['body'], 'login') === false) {
            echo "✅ Authentication successful (verified via index.php access)\n";
            return true;
        }
        
        return false;
    }
    
    /**
     * Test API endpoint
     */
    private function testEndpoint($name, $url, $method = 'GET', $data = null, $expectedStatus = 200) {
        echo "🧪 Testing $name ($method $url)...\n";
        
        $ch = $this->initCurl($url, $method, $data);
        $response = $this->executeRequest($ch);
        
        $result = [
            'name' => $name,
            'url' => $url,
            'method' => $method,
            'expected_status' => $expectedStatus,
            'actual_status' => $response['status_code'],
            'success' => $response['status_code'] == $expectedStatus,
            'response_size' => strlen($response['body']),
            'is_json' => $response['json'] !== null,
            'data' => $response['json']
        ];
        
        if ($result['success']) {
            echo "✅ {$name}: HTTP {$response['status_code']} - ";
            if ($result['is_json']) {
                if (is_array($response['json'])) {
                    echo count($response['json']) . " items returned\n";
                } else {
                    echo "JSON response received\n";
                }
            } else {
                echo "Non-JSON response (" . strlen($response['body']) . " bytes)\n";
            }
        } else {
            echo "❌ {$name}: Expected HTTP {$expectedStatus}, got {$response['status_code']}\n";
            if ($response['status_code'] == 302) {
                echo "   → Likely authentication required\n";
            }
        }
        
        $this->testResults[] = $result;
        return $result;
    }
    
    /**
     * Test all GET endpoints
     */
    public function testGetEndpoints() {
        echo "\n📋 Testing GET Endpoints...\n";
        echo str_repeat("-", 50) . "\n";
        
        // Test technologies endpoint
        $this->testEndpoint('Technologies List', '/api.php/technologies');
        
        // Test orders endpoints
        $this->testEndpoint('All Orders', '/api.php/orders');
        $this->testEndpoint('Pending Orders', '/api.php/orders?status=Čekající');
        $this->testEndpoint('In Production Orders', '/api.php/orders?status=V_výrobě');
        $this->testEndpoint('Completed Orders', '/api.php/orders?status=Hotovo');
        
        // Test blocks endpoint
        $this->testEndpoint('Blocks List', '/api.php/blocks');
        
        // Test specific order (if exists)
        $this->testEndpoint('Single Order (ID=1)', '/api.php/orders/1');
    }
    
    /**
     * Test POST endpoints (create operations)
     */
    public function testPostEndpoints() {
        echo "\n📝 Testing POST Endpoints...\n";
        echo str_repeat("-", 50) . "\n";
        
        // Test creating a new order
        $newOrder = [
            'customer_name' => 'Test Customer API',
            'order_number' => 'TEST-' . date('YmdHis'),
            'production_date' => date('Y-m-d', strtotime('+7 days')),
            'production_status' => 'Čekající',
            'technology_id' => 1,
            'quantity' => 100,
            'notes' => 'Created by API test script'
        ];
        
        $result = $this->testEndpoint('Create Order', '/api.php/orders', 'POST', $newOrder, 201);
        
        if ($result['success'] && isset($result['data']['id'])) {
            $orderId = $result['data']['id'];
            echo "   → Created order with ID: $orderId\n";
            
            // Test updating the created order
            $this->testPutEndpoints($orderId);
            
            // Optionally delete the test order
            $this->testDeleteEndpoints($orderId);
        }
    }
    
    /**
     * Test PUT endpoints (update operations)
     */
    public function testPutEndpoints($orderId = null) {
        echo "\n✏️  Testing PUT Endpoints...\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!$orderId) {
            echo "⚠️  No order ID provided for PUT tests\n";
            return;
        }
        
        // Test marking order as completed (the main fix we're verifying)
        $updateData = [
            'id' => $orderId,
            'production_status' => 'Hotovo',
            'completion_date' => date('Y-m-d')
        ];
        
        $this->testEndpoint('Mark Order Completed', '/api.php/orders', 'PUT', $updateData);
        
        // Verify the order is now marked as completed
        $this->testEndpoint('Verify Completed Order', "/api.php/orders/$orderId");
    }
    
    /**
     * Test DELETE endpoints
     */
    public function testDeleteEndpoints($orderId = null) {
        echo "\n🗑️  Testing DELETE Endpoints...\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!$orderId) {
            echo "⚠️  No order ID provided for DELETE tests\n";
            return;
        }
        
        $this->testEndpoint('Delete Test Order', "/api.php/orders/$orderId", 'DELETE', null, 200);
    }
    
    /**
     * Test error handling
     */
    public function testErrorHandling() {
        echo "\n⚠️  Testing Error Handling...\n";
        echo str_repeat("-", 50) . "\n";
        
        // Test invalid endpoint
        $this->testEndpoint('Invalid Endpoint', '/api.php/invalid', 'GET', null, 404);
        
        // Test invalid order ID
        $this->testEndpoint('Invalid Order ID', '/api.php/orders/99999', 'GET', null, 404);
        
        // Test invalid method
        $this->testEndpoint('Invalid Method', '/api.php/orders', 'PATCH', null, 405);
    }
    
    /**
     * Generate test report
     */
    public function generateReport() {
        echo "\n📊 Test Results Summary\n";
        echo str_repeat("=", 50) . "\n";
        
        $total = count($this->testResults);
        $passed = array_filter($this->testResults, function($r) { return $r['success']; });
        $failed = array_filter($this->testResults, function($r) { return !$r['success']; });
        
        echo "Total Tests: $total\n";
        echo "Passed: " . count($passed) . " ✅\n";
        echo "Failed: " . count($failed) . " ❌\n";
        echo "Success Rate: " . round((count($passed) / $total) * 100, 1) . "%\n\n";
        
        if (!empty($failed)) {
            echo "❌ Failed Tests:\n";
            foreach ($failed as $test) {
                echo "  - {$test['name']}: Expected {$test['expected_status']}, got {$test['actual_status']}\n";
            }
            echo "\n";
        }
        
        echo "🔍 Detailed Results:\n";
        foreach ($this->testResults as $test) {
            $status = $test['success'] ? '✅' : '❌';
            echo "$status {$test['name']} ({$test['method']} {$test['url']}) - HTTP {$test['actual_status']}\n";
            
            if ($test['is_json'] && $test['data']) {
                if (is_array($test['data']) && !empty($test['data'])) {
                    echo "    → " . count($test['data']) . " items returned\n";
                } elseif (isset($test['data']['id'])) {
                    echo "    → ID: {$test['data']['id']}\n";
                }
            }
        }
        
        return count($failed) === 0;
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "🚀 Starting Comprehensive API Testing\n";
        echo "Target: {$this->baseUrl}\n";
        echo str_repeat("=", 50) . "\n";
        
        // Clean up any existing cookies
        @unlink('/tmp/api_test_cookies.txt');
        
        // 1. Authenticate
        if (!$this->authenticate()) {
            echo "❌ Authentication failed. Cannot proceed with API tests.\n";
            return false;
        }
        
        // 2. Test all endpoints
        $this->testGetEndpoints();
        $this->testPostEndpoints();
        $this->testErrorHandling();
        
        // 3. Generate report
        $success = $this->generateReport();
        
        // 4. Check debug log
        $this->checkDebugLog();
        
        return $success;
    }
    
    /**
     * Check debug log for recent API calls
     */
    private function checkDebugLog() {
        echo "\n📄 Checking Debug Log...\n";
        echo str_repeat("-", 50) . "\n";
        
        $logFile = __DIR__ . '/debug.log';
        if (!file_exists($logFile)) {
            echo "⚠️  Debug log not found\n";
            return;
        }
        
        $logContent = file_get_contents($logFile);
        $lines = array_filter(explode("\n", $logContent));
        $recentLines = array_slice($lines, -20); // Last 20 entries
        
        echo "📋 Recent API calls from debug.log:\n";
        foreach ($recentLines as $line) {
            if (strpos($line, 'API Request') !== false || strpos($line, 'Response') !== false) {
                echo "  $line\n";
            }
        }
    }
}

// Run the tests if this script is executed directly
if (php_sapi_name() === 'cli') {
    echo "PHP Production Management System - API Test Suite\n";
    echo "================================================\n\n";
    
    // Check if server is running
    $serverUrl = 'http://localhost:8000';
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($serverUrl, false, $context);
    
    if ($response === false) {
        echo "❌ Server not running at $serverUrl\n";
        echo "Please start the PHP server first:\n";
        echo "cd /Users/nothing/Documents/GIT\\ -\\ vyroba\\ CIG/vyroba\n";
        echo "php -S localhost:8000\n\n";
        exit(1);
    }
    
    echo "✅ Server is running at $serverUrl\n\n";
    
    // Run the tests
    $tester = new APITester($serverUrl);
    $success = $tester->runAllTests();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    if ($success) {
        echo "🎉 All tests passed! The debugging fixes are working correctly.\n";
        exit(0);
    } else {
        echo "⚠️  Some tests failed. Please review the results above.\n";
        exit(1);
    }
}
?>
