<?php
// Set CORS headers first, before any authentication
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check admin token
function checkAdminAuth() {
    $adminToken = getenv('ADMIN_TOKEN');
    
    if (!$adminToken) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Admin token not configured'
        ]);
        exit;
    }
    
    // Check for token in header or query parameter
    $token = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if ($token !== $adminToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }
}

// Get storage configuration
$config = Config::Get('storage');
$storageId = $config['storageId'];
$storageConfig = $config['storages'][$storageId];

// Parse request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// List all logs
if (count($pathParts) == 2 && $pathParts[1] == 'logs') {
    checkAdminAuth();
    
    $logs = [];
    
    try {
        if ($storageId == 'f') {
            // Filesystem storage
            $fsConfig = Config::Get('filesystem');
            $basePath = CORE_PATH . $fsConfig['path'];
            
            if (is_dir($basePath)) {
                $files = scandir($basePath);
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..' || $file == '.gitignore') {
                        continue;
                    }
                    
                    $filePath = $basePath . $file;
                    if (is_file($filePath)) {
                        $logs[] = [
                            'id' => $file,
                            'size' => filesize($filePath),
                            'created' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                    }
                }
            }
        } elseif ($storageId == 'r') {
            // Redis storage
            \Client\RedisClient::Connect();
            $redis = \Client\RedisClient::$connection;
            
            $keys = $redis->keys('*');
            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                $size = strlen($redis->get($key));
                
                $logs[] = [
                    'id' => $key,
                    'size' => $size,
                    'created' => 'N/A',
                    'ttl' => $ttl > 0 ? $ttl : null
                ];
            }
        } elseif ($storageId == 'm') {
            // MongoDB storage - basic implementation
            echo json_encode([
                'success' => false,
                'error' => 'MongoDB listing not implemented. Please use filesystem or Redis storage.'
            ]);
            exit;
        }
        
        // Sort by created date (newest first)
        usort($logs, function($a, $b) {
            if (!isset($a['created']) || !isset($b['created'])) return 0;
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'storage_type' => $storageConfig['name'],
            'count' => count($logs)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to list logs: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Delete a log
if (count($pathParts) == 3 && $pathParts[1] == 'delete') {
    checkAdminAuth();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }
    
    $logId = $pathParts[2];
    
    try {
        $deleted = false;
        
        if ($storageId == 'f') {
            // Filesystem storage
            $fsConfig = Config::Get('filesystem');
            $basePath = CORE_PATH . $fsConfig['path'];
            $filePath = $basePath . $logId;
            
            if (file_exists($filePath)) {
                $deleted = unlink($filePath);
            }
        } elseif ($storageId == 'r') {
            // Redis storage
            \Client\RedisClient::Connect();
            $redis = \Client\RedisClient::$connection;
            
            $deleted = $redis->del($logId) > 0;
        } elseif ($storageId == 'm') {
            // MongoDB storage - basic implementation
            echo json_encode([
                'success' => false,
                'error' => 'MongoDB deletion not implemented. Please use filesystem or Redis storage.'
            ]);
            exit;
        }
        
        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Log deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Log not found'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete log: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Invalid endpoint
http_response_code(404);
echo json_encode([
    'success' => false,
    'error' => 'Endpoint not found'
]);
