<?php

require_once("../../core/core.php");
require_once("../../core/config/urls.php");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($query ?: '', $params);

// Handle admin logout FIRST, before any output
if ($uri === '/admin' && isset($params['logout'])) {
    @session_start();
    $_SESSION = array();
    
    // Get session name
    $sessionName = session_name();
    
    // Delete session cookie
    if (isset($_COOKIE[$sessionName])) {
        setcookie($sessionName, '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[$sessionName]);
    }
    
    // Destroy session
    @session_destroy();
    
    // Send redirect with cache control headers
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Location: /admin', true, 302);
    die();
}

// Handle admin proxy routes
if (str_starts_with($uri, '/admin-proxy/')) {
    require_once("../endpoints/admin-proxy.php");
    exit;
}

switch ($uri) {
    case "/":
        require_once("../frontend/main.php");
        break;
    case "/admin":
        require_once("../frontend/admin.php");
        break;
    default:
        require_once("../frontend/logview.php");
        break;
}

?>

<script>
    window.MCLOGS_CONFIG = {
        apiBaseUrl: '<?php echo $config["apiBaseUrl"]; ?>'
    };
</script>
