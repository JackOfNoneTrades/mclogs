<?php
// This proxy handles admin API calls without exposing the token to the frontend
session_start();

// Check if user is authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get the admin token from environment (never expose this to frontend)
$adminToken = getenv('ADMIN_TOKEN');
if (!$adminToken) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Admin token not configured']);
    exit;
}

// Get API base URL
require_once("../../core/core.php");
require_once("../../core/config/urls.php");
$apiBaseUrl = $config["apiBaseUrl"];

// Parse the request
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$query = parse_url($requestUri, PHP_URL_QUERY);

// Extract the API path (everything after /admin-proxy/)
$apiPath = str_replace('/admin-proxy/', '/admin/', $path);

// Build the full API URL with token and preserve existing query parameters
$apiUrl = $apiBaseUrl . $apiPath . '?token=' . urlencode($adminToken);
if ($query) {
    $apiUrl .= '&' . $query;
}

// Forward the request to the API
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Forward the HTTP method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    // Forward POST body
    $postData = file_get_contents('php://input');
    if ($postData) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
}

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Return the response
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;
