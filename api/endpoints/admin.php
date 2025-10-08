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
    
    if (!$adminToken || $adminToken === false) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Admin token not configured in .env file'
        ]);
        exit;
    }
    
    // Check for token in header or query parameter
    $token = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    } else {
        // Try parsing from REQUEST_URI if $_GET is not populated
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (isset($params['token'])) {
                $token = $params['token'];
            }
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No token provided',
            'debug' => [
                'get_available' => !empty($_GET),
                'get_keys' => array_keys($_GET),
                'request_uri' => $_SERVER['REQUEST_URI'],
                'query_string' => $_SERVER['QUERY_STRING'] ?? 'not set'
            ]
        ]);
        exit;
    }
    
    // Trim both tokens to avoid whitespace issues
    $adminToken = trim($adminToken);
    $token = trim($token);
    
    if ($token !== $adminToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid token',
            'debug' => [
                'token_length' => strlen($token),
                'expected_length' => strlen($adminToken),
                'token_provided' => substr($token, 0, 3) . '...'
            ]
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
            // MongoDB storage - access via reflection to get protected method
            $mongoClass = new ReflectionClass('\Storage\Mongo');
            $collectionMethod = $mongoClass->getMethod('getCollection');
            $collectionMethod->setAccessible(true);
            $collection = $collectionMethod->invoke(null);
            
            $cursor = $collection->find([], ['projection' => ['_id' => 1, 'expires' => 1, 'data' => 1]]);
            foreach ($cursor as $doc) {
                $size = isset($doc->data) ? strlen($doc->data) : 0;
                $created = isset($doc->expires) ? date('Y-m-d H:i:s', $doc->expires->toDateTime()->getTimestamp()) : 'N/A';
                
                // MongoDB stores raw IDs - reconstruct the full ID with storage prefix
                $rawId = $doc->_id;
                $idObj = new \Id();
                $idObj->setStorage('m'); // MongoDB storage type
                
                // Use reflection to set the private rawId property
                $idReflection = new ReflectionClass('\Id');
                $rawIdProperty = $idReflection->getProperty('rawId');
                $rawIdProperty->setAccessible(true);
                $rawIdProperty->setValue($idObj, $rawId);
                
                // Get the full ID with encoded storage prefix
                $fullId = $idObj->get();
                
                $logs[] = [
                    'id' => $fullId,
                    'size' => $size,
                    'created' => $created
                ];
            }
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
            // MongoDB storage - convert full ID to raw ID
            // The dashboard sends full IDs (e.g., "cJKJ6na") but MongoDB stores raw IDs (e.g., "JKJ6na")
            $idObj = new \Id($logId);
            $rawId = $idObj->getRaw();
            
            // Access MongoDB collection via reflection
            $mongoClass = new ReflectionClass('\Storage\Mongo');
            $collectionMethod = $mongoClass->getMethod('getCollection');
            $collectionMethod->setAccessible(true);
            $collection = $collectionMethod->invoke(null);
            
            $result = $collection->deleteOne(['_id' => $rawId]);
            $deleted = $result->getDeletedCount() > 0;
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
